<?php

namespace App\Services;

use App\Models\AttendanceDay;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\PointEntry;
use App\Models\RankingWeight;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RankingService
{
    /**
     * Build a 0-100 composite ranking for the students of a class.
     *
     * @param  array<int, int>  $classSubjectIds
     */
    public function rank(SchoolClass $class, array $classSubjectIds, string $fromDate, string $toDate, ?int $teacherId = null): Collection
    {
        if ($classSubjectIds === []) {
            return collect();
        }

        $students = $class->students()->orderBy('name')->get();

        if ($students->isEmpty()) {
            return collect();
        }

        $studentIds = $students->pluck('id')->all();

        $pointEntries = PointEntry::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('class_subject_id', $classSubjectIds)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('student_id');

        $attendanceDays = AttendanceDay::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_started', true)
            ->whereBetween('date', [$fromDate, $toDate])
            ->with(['attendances' => fn ($query) => $query->whereIn('student_id', $studentIds)])
            ->get();

        $heldDayIds = $attendanceDays
            ->filter(fn (AttendanceDay $day) => $day->attendances->contains(fn ($attendance) => $attendance->status !== 'cancelled'))
            ->pluck('id')
            ->all();

        $heldDays = count($heldDayIds);
        $absenceDayCounts = [];
        $attendanceCounts = [];

        foreach ($attendanceDays as $day) {
            foreach ($day->attendances as $attendance) {
                $attendanceCounts[$attendance->student_id][$attendance->status] =
                    ($attendanceCounts[$attendance->student_id][$attendance->status] ?? 0) + 1;
            }

            if (! in_array($day->id, $heldDayIds, true)) {
                continue;
            }

            $absentStudentIds = $day->attendances
                ->where('status', 'absent')
                ->pluck('student_id')
                ->unique();

            foreach ($absentStudentIds as $studentId) {
                $absenceDayCounts[$studentId] = ($absenceDayCounts[$studentId] ?? 0) + 1;
            }
        }

        $exams = Exam::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Exam $exam) => $this->inPeriod($this->examPeriodDate($exam), $fromDate, $toDate))
            ->values();

        $attempts = ExamAttempt::query()
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['submitted', 'expired'])
            ->get()
            ->groupBy(fn (ExamAttempt $attempt) => $attempt->exam_id.':'.$attempt->student_id);

        $exercises = Exercise::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Exercise $exercise) => $this->inPeriod($this->exercisePeriodDate($exercise), $fromDate, $toDate))
            ->values();

        $submissions = ExerciseSubmission::query()
            ->whereIn('exercise_id', $exercises->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy(fn (ExerciseSubmission $submission) => $submission->exercise_id.':'.$submission->student_id);

        $rawPoints = $students->mapWithKeys(fn (User $student) => [
            $student->id => (int) ($pointEntries->get($student->id, collect())->sum('points')),
        ]);

        $minPoints = (int) $rawPoints->min();
        $maxPoints = (int) $rawPoints->max();
        $neutralManualScore = (float) config('ranking.manual_points.neutral_score', 50);
        $weights = RankingWeight::weightsForTeacher($teacherId);

        $rows = $students->map(function (User $student) use (
            $pointEntries,
            $attendanceCounts,
            $absenceDayCounts,
            $heldDays,
            $exams,
            $attempts,
            $exercises,
            $submissions,
            $minPoints,
            $maxPoints,
            $neutralManualScore,
            $weights
        ) {
            $entries = $pointEntries->get($student->id, collect());
            $points = (int) $entries->sum('points');
            $stats = $this->buildStatsArray($attendanceCounts[$student->id] ?? []);

            $manualScore = $maxPoints > $minPoints
                ? (($points - $minPoints) / ($maxPoints - $minPoints)) * 100
                : $neutralManualScore;

            $exam = $this->examComponent($student->id, $exams, $attempts);
            $exercise = $this->exerciseComponent($student->id, $exercises, $submissions);
            $absenceDays = $absenceDayCounts[$student->id] ?? 0;
            $attendanceScore = $heldDays > 0
                ? max(0.0, 100 * ($heldDays - min($absenceDays, $heldDays)) / $heldDays)
                : null;

            $components = [
                'exams' => [
                    'score' => $exam['score'],
                    'weight' => (float) ($weights['exams'] ?? 0),
                    'counted' => $exam['counted'],
                    'missed' => $exam['missed'],
                ],
                'exercises' => [
                    'score' => $exercise['score'],
                    'weight' => (float) ($weights['exercises'] ?? 0),
                    'counted' => $exercise['counted'],
                    'missed' => $exercise['missed'],
                ],
                'attendance' => [
                    'score' => $attendanceScore,
                    'weight' => (float) ($weights['attendance'] ?? 0),
                    'held_days' => $heldDays,
                    'absence_days' => $absenceDays,
                ],
                'manual' => [
                    'score' => $manualScore,
                    'weight' => (float) ($weights['manual'] ?? 0),
                    'points' => $points,
                ],
            ];

            $available = collect($components)->filter(fn ($component) => $component['score'] !== null);
            $weightSum = (float) $available->sum('weight');
            $rankScore = $weightSum > 0
                ? $available->sum(fn ($component) => $component['score'] * $component['weight']) / $weightSum
                : 0.0;

            return [
                'student' => $student,
                'points' => $points,
                'positive_sum' => (int) $entries->where('points', '>', 0)->sum('points'),
                'negative_sum' => (int) $entries->where('points', '<', 0)->sum('points'),
                'by_subject' => $this->buildSubjectPoints($entries),
                'stats' => $stats,
                'percentage' => $stats['percentage'],
                'rank_score' => round($rankScore, 2),
                'rank_components' => $components,
            ];
        });

        return $rows
            ->sort(function (array $a, array $b) {
                if ($a['rank_score'] !== $b['rank_score']) {
                    return $b['rank_score'] <=> $a['rank_score'];
                }

                foreach (['exams', 'exercises', 'attendance'] as $component) {
                    $comparison = $this->compareNullableDesc(
                        $a['rank_components'][$component]['score'] ?? null,
                        $b['rank_components'][$component]['score'] ?? null
                    );

                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                if ($a['points'] !== $b['points']) {
                    return $b['points'] <=> $a['points'];
                }

                return strcmp($a['student']->name, $b['student']->name);
            })
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * @return array{score: ?float, counted: int, missed: int}
     */
    private function examComponent(int $studentId, Collection $exams, Collection $attempts): array
    {
        $scores = [];
        $missed = 0;

        foreach ($exams as $exam) {
            $attempt = $attempts
                ->get($exam->id.':'.$studentId)
                ?->sortByDesc('final_score')
                ->first();

            if ($attempt !== null) {
                $scores[] = max(0.0, min(100.0, ((float) $attempt->final_score / 20) * 100));

                continue;
            }

            if ($exam->end_at !== null && $exam->end_at->lte(now())) {
                $scores[] = 0.0;
                $missed++;
            }
        }

        return [
            'score' => $scores === [] ? null : array_sum($scores) / count($scores),
            'counted' => count($scores),
            'missed' => $missed,
        ];
    }

    /**
     * @return array{score: ?float, counted: int, missed: int}
     */
    private function exerciseComponent(int $studentId, Collection $exercises, Collection $submissions): array
    {
        $scores = [];
        $missed = 0;

        foreach ($exercises as $exercise) {
            $submission = $submissions->get($exercise->id.':'.$studentId);

            if ($submission !== null) {
                $maxScore = (float) $exercise->max_score;

                if ($submission->score !== null && $maxScore > 0) {
                    $scores[] = max(0.0, min(100.0, ((float) $submission->score / $maxScore) * 100));
                }

                continue;
            }

            if ($exercise->deadline_at !== null
                && $exercise->deadline_at->lte(now())
                && ! $exercise->allow_late_submission) {
                $scores[] = 0.0;
                $missed++;
            }
        }

        return [
            'score' => $scores === [] ? null : array_sum($scores) / count($scores),
            'counted' => count($scores),
            'missed' => $missed,
        ];
    }

    private function examPeriodDate(Exam $exam): ?Carbon
    {
        return $exam->end_at ?? $exam->start_at ?? $exam->created_at;
    }

    private function exercisePeriodDate(Exercise $exercise): ?Carbon
    {
        return $exercise->deadline_at ?? $exercise->created_at;
    }

    private function inPeriod(?Carbon $date, string $fromDate, string $toDate): bool
    {
        if ($date === null) {
            return false;
        }

        $value = $date->toDateString();

        return $value >= $fromDate && $value <= $toDate;
    }

    private function compareNullableDesc(?float $a, ?float $b): int
    {
        if ($a === null && $b === null) {
            return 0;
        }

        if ($a === null) {
            return 1;
        }

        if ($b === null) {
            return -1;
        }

        return $b <=> $a;
    }

    private function buildSubjectPoints(Collection $entries): Collection
    {
        return $entries->groupBy('class_subject_id')->map(function ($rows, $classSubjectId) {
            return [
                'class_subject_id' => (int) $classSubjectId,
                'total' => (int) $rows->sum('points'),
                'positive' => (int) $rows->where('points', '>', 0)->sum('points'),
                'negative' => (int) $rows->where('points', '<', 0)->sum('points'),
                'count' => $rows->count(),
            ];
        });
    }

    private function buildStatsArray(array $counts): array
    {
        $present = $counts['present'] ?? 0;
        $late = $counts['late'] ?? 0;
        $excused = $counts['excused'] ?? 0;
        $absent = $counts['absent'] ?? 0;
        $left = $counts['left'] ?? 0;
        $cancelled = $counts['cancelled'] ?? 0;

        $totalRecorded = $present + $late + $excused + $absent + $left + $cancelled;
        $totalHeld = $totalRecorded - $cancelled;
        $presentCombined = $present + $late + $excused;

        return [
            'total_recorded' => $totalRecorded,
            'total_held' => $totalHeld,
            'present' => $present,
            'late' => $late,
            'excused' => $excused,
            'absent' => $absent,
            'left' => $left,
            'cancelled' => $cancelled,
            'present_combined' => $presentCombined,
            'percentage' => $totalHeld > 0
                ? round(($presentCombined / $totalHeld) * 100, 1)
                : 0,
        ];
    }
}
