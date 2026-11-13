<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\AcademicYear;
use App\Services\StudentReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function checkAccess(SchoolClass $class)
    {
        if ($class->school_id !== auth()->user()->school_id) {
            abort(403, 'شما به این کلاس دسترسی ندارید.');
        }
    }

    public function classReport(Request $request, SchoolClass $class)
    {
        $this->checkAccess($class);

        $currentYear = AcademicYear::where('is_current', true)->first();
        $service = new StudentReportService();
        $quickFilter = $request->input('quick');
        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);

        $classSubjects = $class->classSubjects()->with('subject')->get();
        $selectedSubjectId = $request->integer('subject');
        $selectedSubject = $selectedSubjectId
            ? $classSubjects->firstWhere('id', $selectedSubjectId)
            : null;

        $ranking = $selectedSubject
            ? $service->getClassRankingBySubject($class, $selectedSubject, $fromDate, $toDate)
            : $service->getClassRanking($class, $fromDate, $toDate);

        $summary = $service->getClassSummary($class, $fromDate, $toDate);
        $subjectSummaries = $service->getClassSubjectSummaries($class, $fromDate, $toDate);
        $subjects = $classSubjects->pluck('subject.name')->filter()->values();

        return view('admin.classes.report', compact(
            'class',
            'currentYear',
            'fromDate',
            'toDate',
            'quickFilter',
            'ranking',
            'summary',
            'subjects',
            'classSubjects',
            'subjectSummaries',
            'selectedSubjectId',
            'selectedSubject'
        ));
    }

    public function fullReport(Request $request, SchoolClass $class)
    {
        $this->checkAccess($class);

        $currentYear = AcademicYear::where('is_current', true)->first();
        $service = new StudentReportService();
        [$fromDate, $toDate] = $service->resolveDateRange(null, null, 'all', $currentYear);

        $ranking = $service->getClassRanking($class, $fromDate, $toDate);
        $summary = $service->getClassSummary($class, $fromDate, $toDate);
        $classSubjects = $class->classSubjects()->with('subject')->get();
        $subjectSummaries = $service->getClassSubjectSummaries($class, $fromDate, $toDate);
        $subjects = $classSubjects->pluck('subject.name')->filter()->values();

        $students = $class->students()->orderBy('name')->get();

        // اگر پارامتر student_id در آدرس بود، فقط همان دانش‌آموز را چاپ کن
        if ($request->has('student_id')) {
            $students = $students->where('id', $request->student_id);
        }

        $rankByStudent = $ranking->keyBy(fn ($r) => $r['student']->id);

        $studentDetails = $students->map(function ($student) use ($service, $fromDate, $toDate, $rankByStudent, $class) {
            $row = $rankByStudent->get($student->id);

            $bySubject = $service->getStatsByClassSubject($student, $fromDate, $toDate);
            $points = $service->getPointsSummary($student, $fromDate, $toDate);
            $absences = $service->getAbsenceDetails($student, $fromDate, $toDate);
            $examResults = $service->getExamResultsForStudent($student);
            $exerciseResults = $service->getExerciseResultsForStudent($student);
            $subjectSummary = $service->getSubjectSummary($student, $fromDate, $toDate);
            $groupedAttendance = $service->getGroupedAttendanceByDate($student, $class, $fromDate, $toDate);

            $stats = $row['stats'] ?? $service->getOverallStats($student, $fromDate, $toDate);
            $rank = $row['rank'] ?? '—';
            $rankScore = $row['rank_score'] ?? null;
            $percentage = $row['percentage'] ?? $stats['percentage'];
            $positiveSum = $row['positive_sum'] ?? 0;
            $negativeSum = $row['negative_sum'] ?? 0;
            $status = $service->getStatusLabel($percentage);

            return [
                'rank' => $rank,
                'rank_score' => $rankScore,
                'rank_components' => $row['rank_components'] ?? [],
                'student' => $student,
                'points' => $points,
                'stats' => $stats,
                'percentage' => $percentage,
                'positive_sum' => $positiveSum,
                'negative_sum' => $negativeSum,
                'bySubject' => $bySubject,
                'absences' => $absences,
                'examResults' => $examResults,
                'exerciseResults' => $exerciseResults,
                'subjectSummary' => $subjectSummary,
                'groupedAttendance' => $groupedAttendance,
                'status' => $status,
            ];
        });

        return view('admin.classes.full-report', compact('class', 'currentYear', 'fromDate', 'toDate', 'ranking', 'summary', 'subjects', 'studentDetails', 'classSubjects', 'subjectSummaries'));
    }

    public function studentProfile(Request $request, User $student)
    {
        if (!$student->is_student) abort(404);

        // بررسی اینکه آیا این دانش‌آموز در مدرسه این مدیر است یا خیر
        $hasAccess = $student->classes()->where('school_id', auth()->user()->school_id)->exists();
        if (!$hasAccess) abort(403);

        $currentYear = AcademicYear::where('is_current', true)->first();
        $service = new StudentReportService();

        $quickFilter = $request->input('quick');
        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);
        $quickFilter = 'all';

        $overall = $service->getOverallStats($student, $fromDate, $toDate);
        $bySubject = $service->getStatsByClassSubject($student, $fromDate, $toDate);
        $points = $service->getPointsSummary($student, $fromDate, $toDate);
        $absences = $service->getAbsenceDetails($student, $fromDate, $toDate);
        $status = $service->getStatusLabel($overall['percentage']);
        $examResults = $service->getExamResultsForStudent($student);
        $exerciseResults = $service->getExerciseResultsForStudent($student);
        $groupedAttendance = $service->getGroupedAttendanceByClassSubject($student, $fromDate, $toDate);

        return view('admin.students.show', compact('student', 'currentYear', 'fromDate', 'toDate', 'quickFilter', 'overall', 'bySubject', 'points', 'absences', 'status', 'examResults', 'exerciseResults', 'groupedAttendance'));
    }
}
