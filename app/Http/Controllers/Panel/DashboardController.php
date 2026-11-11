<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\StudentReportService;
use App\Services\ExamService;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;

class DashboardController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (!$currentYear) {
            return view('panel.dashboard-no-year', compact('student'));
        }

        $service = new StudentReportService();
        $examService = new ExamService();

        [$fromDate, $toDate] = $service->resolveDateRange(null, null, 'all', $currentYear);

        $overall = $service->getOverallStats($student, $fromDate, $toDate);
        $points = $service->getPointsSummary($student, $fromDate, $toDate);
        $status = $service->getStatusLabel($overall['percentage']);

        $class = $student->classes()->first();
        $rank = '—';
        $totalStudents = 0;
        if ($class) {
            $ranking = $service->getClassRanking($class, $fromDate, $toDate);
            $rankRow = $ranking->firstWhere('student.id', $student->id);
            $rank = $rankRow ? $rankRow['rank'] : '—';
            $totalStudents = $ranking->count();
        }

        // محاسبه تلاش‌ها یکجا
        $attemptsByExam = \App\Models\ExamAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->get()
            ->groupBy('exam_id');

        $allExams = $examService->getAvailableExamsForStudent($student);

        // فقط آزمون‌های جدید (شرکت نکرده)
        $newExams = $allExams->filter(function($exam) use ($attemptsByExam) {
            if (!$exam->isOpenNow()) return false;
            return ($attemptsByExam[$exam->id] ?? collect())->count() === 0;
        })->take(4);

        // آزمون‌های قابل تکرار (برای نشان دادن یک هشدار کوچک)
        $retakeableCount = $allExams->filter(function($exam) use ($attemptsByExam) {
            if (!$exam->isOpenNow()) return false;
            $done = ($attemptsByExam[$exam->id] ?? collect())->count();
            return $done > 0 && $done < $exam->max_attempts;
        })->count();

        $classIds = $student->classes()->pluck('school_classes.id')->toArray();
        $activeExercises = Exercise::whereHas('classSubject', fn($q) => $q->whereIn('school_class_id', $classIds))
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('deadline_at')->orWhere('deadline_at', '>=', now());
            })
            ->with('classSubject.subject')
            ->orderBy('deadline_at', 'asc')
            ->take(4)
            ->get();

        return view('panel.dashboard', compact(
            'student', 'currentYear', 'overall', 'points', 'status',
            'rank', 'totalStudents', 'newExams', 'retakeableCount', 'activeExercises'
        ));
    }
}
