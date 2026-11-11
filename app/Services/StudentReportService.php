<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\PointEntry;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentReportService
{
    /**
     * تعیین بازه تاریخ بر اساس فیلترهای ورودی
     *
     * @return array{0: string, 1: string}  [fromDate, toDate] به فرمت Y-m-d
     */
    public function resolveDateRange(?string $fromInput, ?string $toInput, ?string $quickFilter, AcademicYear $year): array
    {
        $today = now()->toDateString();
        $yearStart = $year->start_date?->format('Y-m-d') ?? $today;

        if ($quickFilter) {
            return match ($quickFilter) {
                'this_month'   => [$this->firstDayOfCurrentJalaliMonth() ?: $yearStart, $today],
                'last_30_days' => [now()->subDays(29)->toDateString(), $today],
                default        => [$yearStart, $today],
            };
        }

        $fromDate = $fromInput ? (to_gregorian($fromInput) ?: $yearStart) : $yearStart;
        $toDate   = $toInput   ? (to_gregorian($toInput)   ?: $today)     : $today;

        // تور اطمینان نهایی
        $fromDate = $fromDate ?: $yearStart;
        $toDate   = $toDate   ?: $today;

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    /**
     * آمار حضور برای یک درس در بازه
     */
    public function getAttendanceStats(User $student, ClassSubject $classSubject, string $fromDate, string $toDate): array
    {
        $counts = Attendance::query()
            ->where('student_id', $student->id)
            ->whereHas('attendanceDay', function ($q) use ($classSubject, $fromDate, $toDate) {
                $q->where('class_subject_id', $classSubject->id)
                    ->whereBetween('date', [$fromDate, $toDate]);
            })
            ->pluck('status')
            ->countBy()
            ->all();

        return $this->buildStatsArray($counts);
    }

    /**
     * آمار به تفکیک هر درس (ClassSubject) برای دانش‌آموز
     */
    public function getStatsByClassSubject(
        User $student,
        string $fromDate,
        string $toDate,
        ?array $onlyClassSubjectIds = null
    ): Collection {
        $query = ClassSubject::query()
            ->whereHas('schoolClass.students', function ($q) use ($student) {
                $q->where('users.id', $student->id);
            })
            ->with(['subject', 'schoolClass.school']);

        if ($onlyClassSubjectIds !== null) {
            $query->whereIn('id', $onlyClassSubjectIds);
        }

        $classSubjects = $query->get();

        return $classSubjects->map(function (ClassSubject $cs) use ($student, $fromDate, $toDate) {
            $stats = $this->getAttendanceStats($student, $cs, $fromDate, $toDate);

            return array_merge($stats, [
                'class_subject_id' => $cs->id,
                'subject_name'     => $cs->subject?->name ?? '—',
                'class_name'       => $cs->schoolClass?->name ?? '—',
                'school_name'      => $cs->schoolClass?->school?->name ?? '—',
            ]);
        });
    }

    /**
     * آمار کل (جمع همه دروس)
     */
    public function getOverallStats(
        User $student,
        string $fromDate,
        string $toDate,
        ?array $onlyClassSubjectIds = null
    ): array {
        $bySubject = $this->getStatsByClassSubject($student, $fromDate, $toDate, $onlyClassSubjectIds);

        $sum = [
            'total_recorded'   => (int) $bySubject->sum('total_recorded'),
            'total_held'       => (int) $bySubject->sum('total_held'),
            'present'          => (int) $bySubject->sum('present'),
            'late'             => (int) $bySubject->sum('late'),
            'excused'          => (int) $bySubject->sum('excused'),
            'absent'           => (int) $bySubject->sum('absent'),
            'left'             => (int) $bySubject->sum('left'),
            'cancelled'        => (int) $bySubject->sum('cancelled'),
            'present_combined' => (int) $bySubject->sum('present_combined'),
        ];

        $sum['percentage'] = $sum['total_held'] > 0
            ? round(($sum['present_combined'] / $sum['total_held']) * 100, 1)
            : 0;

        return $sum;
    }

    /**
     * خلاصه امتیازات دستی در بازه
     */
    public function getPointsSummary(User $student, string $fromDate, string $toDate): array
    {
        $entries = PointEntry::query()
            ->where('student_id', $student->id)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->with(['category', 'classSubject.subject', 'createdBy'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        return [
            'entries'  => $entries,
            'total'    => (int) $entries->sum('points'),
            'positive' => (int) $entries->where('points', '>', 0)->sum('points'),
            'negative' => (int) $entries->where('points', '<', 0)->sum('points'),
            'count'    => $entries->count(),
        ];
    }

    /**
     * ریز غیبت‌ها، تاخیرها و ترک کلاس‌ها در بازه
     */
    public function getAbsenceDetails(User $student, string $fromDate, string $toDate): Collection
    {
        return Attendance::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['absent', 'late', 'left'])
            ->whereHas('attendanceDay', function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('date', [$fromDate, $toDate]);
            })
            ->with(['attendanceDay.classSubject.subject'])
            ->get()
            ->sortByDesc(fn($a) => $a->attendanceDay?->date?->format('Y-m-d') ?? '')
            ->values();
    }

    /**
     * رتبه‌بندی دانش‌آموزان یک کلاس در بازه
     */
    public function getClassRanking(SchoolClass $class, string $fromDate, string $toDate): Collection
    {
        $classSubjectIds = $class->classSubjects()->pluck('id')->toArray();

        if (empty($classSubjectIds)) {
            return collect();
        }

        $students = $class->students()->orderBy('name')->get();

        $rows = $students->map(function (User $student) use ($classSubjectIds, $fromDate, $toDate) {
            $entries = PointEntry::query()
                ->where('student_id', $student->id)
                ->whereIn('class_subject_id', $classSubjectIds)
                ->whereBetween('entry_date', [$fromDate, $toDate])
                ->get();

            $points      = (int) $entries->sum('points');
            $positiveSum = (int) $entries->where('points', '>', 0)->sum('points');
            $negativeSum = (int) $entries->where('points', '<', 0)->sum('points');

            $stats = $this->getOverallStats($student, $fromDate, $toDate, $classSubjectIds);

            return [
                'student'      => $student,
                'points'       => $points,
                'positive_sum' => $positiveSum,
                'negative_sum' => $negativeSum,
                'stats'        => $stats,
                'percentage'   => $stats['percentage'],
            ];
        });

        return $rows
            ->sort(function ($a, $b) {
                if ($b['points'] !== $a['points']) {
                    return $b['points'] <=> $a['points'];
                }
                return $b['percentage'] <=> $a['percentage'];
            })
            ->values()
            ->map(function ($row, $i) {
                $row['rank'] = $i + 1;
                return $row;
            });
    }

    /**
     * خلاصه آمار کلاس در بازه
     */
    public function getClassSummary(SchoolClass $class, string $fromDate, string $toDate): array
    {
        $ranking = $this->getClassRanking($class, $fromDate, $toDate);

        if ($ranking->isEmpty()) {
            return [
                'students_count' => 0,
                'avg_points'     => 0,
                'avg_percentage' => 0,
                'total_absences' => 0,
                'total_lates'    => 0,
            ];
        }

        return [
            'students_count' => $ranking->count(),
            'avg_points'     => round($ranking->avg('points'), 1),
            'avg_percentage' => round($ranking->avg('percentage'), 1),
            'total_absences' => (int) $ranking->sum(fn($r) => $r['stats']['absent']),
            'total_lates'    => (int) $ranking->sum(fn($r) => $r['stats']['late']),
        ];
    }

    /**
     * برچسب وضعیت بر اساس درصد
     *
     * @return array{label: string, color: string}
     */
    public function getStatusLabel(float $percentage): array
    {
        if ($percentage >= 95) {
            return ['label' => 'عالی', 'color' => 'success'];
        }
        if ($percentage >= 85) {
            return ['label' => 'خوب', 'color' => 'info'];
        }
        if ($percentage >= 70) {
            return ['label' => 'نیاز به بهبود', 'color' => 'warning'];
        }
        return ['label' => 'هشدار', 'color' => 'danger'];
    }

    /**
     * نتایج آزمون‌های برگزار شده برای یک دانش‌آموز
     * (مستقل از فیلتر تاریخ — همه آزمون‌های برگزار شده را برمی‌گرداند)
     */
    public function getExamResultsForStudent(User $student, ?array $onlyClassSubjectIds = null): Collection
    {
        // درس‌های کلاس‌هایی که دانش‌آموز در آن‌ها ثبت‌نام است
        $classSubjectIds = $onlyClassSubjectIds ?? ClassSubject::query()
            ->whereHas('schoolClass.students', function ($q) use ($student) {
                $q->where('users.id', $student->id);
            })
            ->pluck('id')
            ->toArray();

        if (empty($classSubjectIds)) {
            return collect();
        }

        // فقط آزمون‌هایی که شروع شده‌اند (در انتظار شروع نباشند)
        $exams = Exam::with(['classSubject.subject', 'classSubject.schoolClass'])
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where(function ($q) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', now());
            })
            ->orderByDesc('start_at')
            ->orderByDesc('created_at')
            ->get();

        return $exams->map(function ($exam) use ($student) {
            $attempts = $exam->attempts()
                ->where('student_id', $student->id)
                ->whereIn('status', ['submitted', 'expired'])
                ->get()
                ->sortByDesc('final_score')
                ->values();

            return [
                'exam'           => $exam,
                'attempts'       => $attempts,
                'attempts_count' => $attempts->count(),
                'best_attempt'   => $attempts->first(),
                'participated'   => $attempts->count() > 0,
            ];
        });
    }

    /**
     * خلاصه به تفکیک درس (برای گزارش کامل)
     * شامل: حضور، غایب، امتیاز دستی، میانگین آزمون‌ها، وضعیت
     */
    public function getSubjectSummary(User $student): Collection
    {
        $classSubjects = ClassSubject::query()
            ->whereHas('schoolClass.students', function ($q) use ($student) {
                $q->where('users.id', $student->id);
            })
            ->with(['subject', 'schoolClass'])
            ->get();

        return $classSubjects->map(function (ClassSubject $cs) use ($student) {
            // حضور و غیاب (کل تاریخچه این درس)
            $counts = Attendance::where('student_id', $student->id)
                ->whereHas('attendanceDay', fn($q) => $q->where('class_subject_id', $cs->id))
                ->pluck('status')
                ->countBy()
                ->all();

            $present   = $counts['present']   ?? 0;
            $late      = $counts['late']      ?? 0;
            $excused   = $counts['excused']   ?? 0;
            $absent    = $counts['absent']    ?? 0;
            $left      = $counts['left']      ?? 0;
            $cancelled = $counts['cancelled'] ?? 0;

            $totalRecorded   = $present + $late + $excused + $absent + $left + $cancelled;
            $totalHeld       = $totalRecorded - $cancelled;
            $presentCombined = $present + $late + $excused;
            $percentage      = $totalHeld > 0
                ? round(($presentCombined / $totalHeld) * 100, 1)
                : 0;

            // امتیاز دستی این درس
            $points = (int) PointEntry::where('student_id', $student->id)
                ->where('class_subject_id', $cs->id)
                ->sum('points');

            // نتایج آزمون‌های این درس
            $examRows = $this->getExamResultsForStudent($student, [$cs->id]);
            $bestScores = $examRows->map(fn($r) => $r['best_attempt']?->final_score)->filter();
            $avgExam    = $bestScores->isNotEmpty() ? round($bestScores->avg(), 1) : null;
            $examsTaken = $examRows->where('participated', true)->count();
            $examsTotal = $examRows->count();

            return [
                'class_subject_id' => $cs->id,
                'subject_name'     => $cs->subject?->name ?? '—',
                'class_name'       => $cs->schoolClass?->name ?? '—',
                'present'          => $present,
                'late'             => $late,
                'excused'          => $excused,
                'absent'           => $absent,
                'total_held'       => $totalHeld,
                'percentage'       => $percentage,
                'status'           => $this->getStatusLabel($percentage),
                'points'           => $points,
                'avg_exam'         => $avgExam,
                'exams_taken'      => $examsTaken,
                'exams_total'      => $examsTotal,
            ];
        })->sortBy('subject_name')->values();
    }

    /**
     * نتایج تمرین‌های یک دانش‌آموز
     * (همه تمرین‌های درس‌های دانش‌آموز — اعم از شرکت‌کرده و نکرده)
     */
    public function getExerciseResultsForStudent(User $student, ?array $onlyClassSubjectIds = null): Collection
    {
        $classSubjectIds = $onlyClassSubjectIds ?? ClassSubject::query()
            ->whereHas('schoolClass.students', function ($q) use ($student) {
                $q->where('users.id', $student->id);
            })
            ->pluck('id')
            ->toArray();

        if (empty($classSubjectIds)) {
            return collect();
        }

        $exercises = Exercise::with(['classSubject.subject', 'classSubject.schoolClass'])
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_active', true)
            ->orderByDesc('deadline_at')
            ->orderByDesc('created_at')
            ->get();

        $submissions = ExerciseSubmission::where('student_id', $student->id)
            ->whereIn('exercise_id', $exercises->pluck('id'))
            ->get()
            ->keyBy('exercise_id');

        return $exercises->map(function ($exercise) use ($submissions) {
            $submission = $submissions->get($exercise->id);

            return [
                'exercise'    => $exercise,
                'submission'  => $submission,
                'submitted'   => $submission !== null,
                'is_graded'   => $submission?->isGraded() ?? false,
                'score'       => $submission?->score,
                'max_score'   => $exercise->max_score,
                'is_late'     => $submission?->is_late ?? false,
                'submitted_at'=> $submission?->submitted_at,
            ];
        });
    }

    /**
     * گروه‌بندی حضور و غیاب بر اساس روز (برای گزارش کامل)
     * خروجی: مجموعه‌ای از ردیف‌ها با تاریخ + آرایه ۴ زنگ
     */
    public function getGroupedAttendanceByDate(
        User $student,
        SchoolClass $class,
        string $fromDate,
        string $toDate
    ): Collection {
        $classSubjectIds = $class->classSubjects()->pluck('id')->toArray();

        if (empty($classSubjectIds)) {
            return collect();
        }

        $days = AttendanceDay::whereIn('class_subject_id', $classSubjectIds)
            ->whereBetween('date', [$fromDate, $toDate])
            ->with(['attendances' => fn($q) => $q->where('student_id', $student->id)])
            ->get();

        $byDate = $days->groupBy(fn($d) => $d->date->format('Y-m-d'));

        return $byDate->map(function ($daysForDate, $dateStr) {
            $periods = [
                1 => ['status' => null, 'note' => null],
                2 => ['status' => null, 'note' => null],
                3 => ['status' => null, 'note' => null],
                4 => ['status' => null, 'note' => null],
            ];

            foreach ($daysForDate as $day) {
                $startPeriod   = (int) ($day->start_period ?? 1);
                $sessionsCount = (int) ($day->sessions_count ?? 4);
                $attendances   = $day->attendances->keyBy('session_number');

                for ($s = 1; $s <= $sessionsCount; $s++) {
                    $periodNum = $startPeriod + $s - 1;
                    if ($periodNum < 1 || $periodNum > 4) continue;

                    $att = $attendances->get($s);
                    $periods[$periodNum] = [
                        'status' => $att?->status,
                        'note'   => $att?->note,
                    ];
                }
            }

            return [
                'date'    => $dateStr,
                'periods' => $periods,
            ];
        })
        ->sortByDesc('date')
        ->values();
    }

    /**
     * گروه‌بندی حضور و غیاب به تفکیک هر ClassSubject (برای گزارش فردی)
     */
    public function getGroupedAttendanceByClassSubject(
        User $student,
        string $fromDate,
        string $toDate
    ): Collection {
        $classSubjects = ClassSubject::query()
            ->whereHas('schoolClass.students', function ($q) use ($student) {
                $q->where('users.id', $student->id);
            })
            ->with(['subject', 'schoolClass.school'])
            ->get();

        return $classSubjects->map(function (ClassSubject $cs) use ($student, $fromDate, $toDate) {
            $days = AttendanceDay::where('class_subject_id', $cs->id)
                ->whereBetween('date', [$fromDate, $toDate])
                ->with(['attendances' => fn($q) => $q->where('student_id', $student->id)])
                ->get();

            $byDate = $days->groupBy(fn($d) => $d->date->format('Y-m-d'));

            $rows = $byDate->map(function ($daysForDate, $dateStr) {
                $periods = [
                    1 => ['status' => null, 'note' => null],
                    2 => ['status' => null, 'note' => null],
                    3 => ['status' => null, 'note' => null],
                    4 => ['status' => null, 'note' => null],
                ];

                foreach ($daysForDate as $day) {
                    $startPeriod   = (int) ($day->start_period ?? 1);
                    $sessionsCount = (int) ($day->sessions_count ?? 4);
                    $attendances   = $day->attendances->keyBy('session_number');

                    for ($s = 1; $s <= $sessionsCount; $s++) {
                        $periodNum = $startPeriod + $s - 1;
                        if ($periodNum < 1 || $periodNum > 4) continue;

                        $att = $attendances->get($s);
                        $periods[$periodNum] = [
                            'status' => $att?->status,
                            'note'   => $att?->note,
                        ];
                    }
                }

                return [
                    'date'    => $dateStr,
                    'periods' => $periods,
                ];
            })
            ->sortByDesc('date')
            ->values();

            return [
                'class_subject' => $cs,
                'rows'          => $rows,
                'has_data'      => $rows->count() > 0,
            ];
        })
        ->filter(fn($item) => $item['has_data'])
        ->values();
    }

    // --- helpers داخلی ---

    private function buildStatsArray(array $counts): array
    {
        $present   = $counts['present']   ?? 0;
        $late      = $counts['late']      ?? 0;
        $excused   = $counts['excused']   ?? 0;
        $absent    = $counts['absent']    ?? 0;
        $left      = $counts['left']      ?? 0;
        $cancelled = $counts['cancelled'] ?? 0;

        $totalRecorded   = $present + $late + $excused + $absent + $left + $cancelled;
        $totalHeld       = $totalRecorded - $cancelled;
        $presentCombined = $present + $late + $excused;

        $percentage = $totalHeld > 0
            ? round(($presentCombined / $totalHeld) * 100, 1)
            : 0;

        return [
            'total_recorded'   => $totalRecorded,
            'total_held'       => $totalHeld,
            'present'          => $present,
            'late'             => $late,
            'excused'          => $excused,
            'absent'           => $absent,
            'left'             => $left,
            'cancelled'        => $cancelled,
            'present_combined' => $presentCombined,
            'percentage'       => $percentage,
        ];
    }

    private function firstDayOfCurrentJalaliMonth(): string
    {
        $formatter = new \IntlDateFormatter(
            'fa_IR@calendar=persian',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Asia/Tehran',
            \IntlDateFormatter::TRADITIONAL,
            'yyyy/MM'
        );

        $currentJalali = $formatter->format(now());

        return to_gregorian($currentJalali . '/01') ?? now()->toDateString();
    }
}
