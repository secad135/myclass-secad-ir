<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassReportService
{
    private StudentReportService $reports;

    public function __construct(?StudentReportService $reports = null)
    {
        $this->reports = $reports ?? new StudentReportService();
    }

    public function reportData(Request $request, SchoolClass $class, AcademicYear $currentYear): array
    {
        $service = $this->reports;

        $quickFilter = $request->input('quick');
        [$fromDate, $toDate] = $service->resolveDateRange(
            $request->input('from'),
            $request->input('to'),
            $quickFilter,
            $currentYear
        );

        $classSubjects = $class->classSubjects()->with('subject')->get();

        $selectedSubjectId = $request->integer('subject');
        $selectedSubject   = $selectedSubjectId
            ? $classSubjects->firstWhere('id', $selectedSubjectId)
            : null;

        $ranking = $selectedSubject
            ? $service->getClassRankingBySubject($class, $selectedSubject, $fromDate, $toDate)
            : $service->getClassRanking($class, $fromDate, $toDate);

        $summary          = $service->getClassSummary($class, $fromDate, $toDate);
        $subjectSummaries = $service->getClassSubjectSummaries($class, $fromDate, $toDate);

        $subjects = $classSubjects->pluck('subject.name')
            ->filter()
            ->values();

        return compact(
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
        );
    }

    public function fullReportData(Request $request, SchoolClass $class, AcademicYear $currentYear): array
    {
        $service = $this->reports;

        $quickFilter = $request->input('quick');
        [$fromDate, $toDate] = $service->resolveDateRange(
            $request->input('from'),
            $request->input('to'),
            $quickFilter,
            $currentYear
        );

        $ranking = $service->getClassRanking($class, $fromDate, $toDate);
        $summary = $service->getClassSummary($class, $fromDate, $toDate);

        $classSubjects    = $class->classSubjects()->with('subject')->get();
        $subjectSummaries = $service->getClassSubjectSummaries($class, $fromDate, $toDate);

        $subjects = $classSubjects->pluck('subject.name')
            ->filter()
            ->values();

        $rankByStudent = $ranking->keyBy(fn ($r) => $r['student']->id);

        $students = $class->students()->orderBy('name')->get();

        $studentDetails = $students->map(function ($student) use ($service, $fromDate, $toDate, $rankByStudent, $class) {
            $row = $rankByStudent->get($student->id);

            $bySubject         = $service->getStatsByClassSubject($student, $fromDate, $toDate);
            $points            = $service->getPointsSummary($student, $fromDate, $toDate);
            $absences          = $service->getAbsenceDetails($student, $fromDate, $toDate);
            $examResults       = $service->getExamResultsForStudent($student);
            $subjectSummary    = $service->getSubjectSummary($student, $fromDate, $toDate);
            $exerciseResults   = $service->getExerciseResultsForStudent($student);
            $groupedAttendance = $service->getGroupedAttendanceByDate($student, $class, $fromDate, $toDate);

            $stats       = $row['stats']        ?? $service->getOverallStats($student, $fromDate, $toDate);
            $rank        = $row['rank']         ?? '—';
            $percentage  = $row['percentage']   ?? $stats['percentage'];
            $positiveSum = $row['positive_sum'] ?? 0;
            $negativeSum = $row['negative_sum'] ?? 0;
            $status      = $service->getStatusLabel($percentage);

            return [
                'rank'              => $rank,
                'student'           => $student,
                'points'            => $points,
                'stats'             => $stats,
                'percentage'        => $percentage,
                'positive_sum'      => $positiveSum,
                'negative_sum'      => $negativeSum,
                'bySubject'         => $bySubject,
                'absences'          => $absences,
                'examResults'       => $examResults,
                'exerciseResults'   => $exerciseResults,
                'subjectSummary'    => $subjectSummary,
                'groupedAttendance' => $groupedAttendance,
                'status'            => $status,
            ];
        });

        return compact(
            'class',
            'currentYear',
            'fromDate',
            'toDate',
            'quickFilter',
            'ranking',
            'summary',
            'subjects',
            'studentDetails',
            'classSubjects',
            'subjectSummaries'
        );
    }
}
