<?php

namespace App\Http\Controllers\Teacher;

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\StudentReportService;
use Illuminate\Http\Request;

class StudentController extends BaseController
{
    public function index()
    {
        $classIds = $this->ownClassIds();

        $students = $this->ownStudentQuery()
            ->withCount([
                'enrollments as enrollments_count' => fn ($q) => $q->whereIn('school_class_id', $classIds),
            ])
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.students.index', compact('students'));
    }

    public function show(Request $request, User $student)
    {
        $this->assertOwnsStudent($student);

        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'ابتدا مدیر باید یک سال تحصیلی «جاری» تعریف کند.');
        }

        $classSubjectIds = $this->ownClassSubjectIdsForOwnClasses();
        $service = new StudentReportService();

        $quickFilter = $request->input('quick');
        $fromInput   = $request->input('from');
        $toInput     = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);

        $overall           = $service->getOverallStats($student, $fromDate, $toDate, $classSubjectIds);
        $bySubject         = $service->getStatsByClassSubject($student, $fromDate, $toDate, $classSubjectIds);
        $points            = $service->getPointsSummary($student, $fromDate, $toDate, $classSubjectIds);
        $absences          = $service->getAbsenceDetails($student, $fromDate, $toDate, $classSubjectIds);
        $groupedAttendance = $service->getGroupedAttendanceByClassSubject($student, $fromDate, $toDate, $classSubjectIds);
        $status            = $service->getStatusLabel($overall['percentage']);
        $examResults       = $service->getExamResultsForStudent($student, $classSubjectIds);
        $exerciseResults   = $service->getExerciseResultsForStudent($student, $classSubjectIds);

        return view('admin.students.show', compact(
            'student', 'currentYear',
            'fromDate', 'toDate', 'quickFilter',
            'overall', 'bySubject', 'points', 'absences', 'status',
            'examResults', 'exerciseResults', 'groupedAttendance'
        ));
    }
}
