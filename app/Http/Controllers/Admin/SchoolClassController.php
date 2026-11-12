<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Services\ExamService;
use App\Services\StudentReportService;

class SchoolClassController extends Controller
{
    public function report(Request $request, SchoolClass $class)
    {
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('success', 'ابتدا یک سال تحصیلی «جاری» تعریف کنید.');
        }

        $service = new StudentReportService();

        $quickFilter = $request->input('quick');
        $fromInput   = $request->input('from');
        $toInput     = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);

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
    public function index()
    {
        $classes = SchoolClass::with(['school', 'academicYear', 'grade', 'teacher'])
            ->latest()
            ->get();

        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $schools  = School::orderBy('name')->get();
        $years    = AcademicYear::orderByDesc('is_current')->orderByDesc('title')->get();
        $grades   = Grade::orderBy('name')->get();
        $teachers = User::orderBy('name')->get();

        return view('admin.classes.create', compact('schools', 'years', 'grades', 'teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'school_id'        => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_id'         => 'nullable|exists:grades,id',
            'teacher_id'       => 'nullable|exists:users,id',
        ]);

        SchoolClass::create($request->only(
            'name',
            'school_id',
            'academic_year_id',
            'grade_id',
            'teacher_id'
        ));

        return redirect()->route('admin.classes.index')
            ->with('success', 'کلاس اضافه شد.');
    }

    public function show(SchoolClass $class)
    {
        $class->load(['school', 'academicYear', 'grade', 'teacher', 'students', 'classSubjects.subject']);

        $enrolledIds = $class->students->pluck('id')->toArray();
        $availableStudents = User::students()
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->get();

        // دروسی که هنوز به این کلاس اضافه نشده‌اند
        $linkedSubjectIds = $class->classSubjects->pluck('subject_id')->toArray();
        $availableSubjects = Subject::whereNotIn('id', $linkedSubjectIds)
            ->orderBy('name')
            ->get();

        return view('admin.classes.show', compact('class', 'availableStudents', 'availableSubjects'));
    }

    public function edit(SchoolClass $class)
    {
        $schools  = School::orderBy('name')->get();
        $years    = AcademicYear::orderByDesc('is_current')->orderByDesc('title')->get();
        $grades   = Grade::orderBy('name')->get();
        $teachers = User::orderBy('name')->get();

        return view('admin.classes.edit', compact('class', 'schools', 'years', 'grades', 'teachers'));
    }

    public function update(Request $request, SchoolClass $class)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'school_id'        => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_id'         => 'nullable|exists:grades,id',
            'teacher_id'       => 'nullable|exists:users,id',
        ]);

        $class->update($request->only(
            'name',
            'school_id',
            'academic_year_id',
            'grade_id',
            'teacher_id'
        ));

        return redirect()->route('admin.classes.index')
            ->with('success', 'کلاس ویرایش شد.');
    }

    public function destroy(SchoolClass $class)
    {
        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', 'کلاس حذف شد.');
    }

    public function enroll(Request $request, SchoolClass $class)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $class->enrollments()->firstOrCreate(
            ['student_id' => $request->student_id],
            ['status' => 'active', 'enrolled_at' => now()]
        );

        return redirect()->route('admin.classes.show', $class)
            ->with('success', 'دانش‌آموز به کلاس اضافه شد.');
    }

    public function unenroll(SchoolClass $class, User $student)
    {
        $class->enrollments()->where('student_id', $student->id)->delete();

        return redirect()->route('admin.classes.show', $class)
            ->with('success', 'دانش‌آموز از کلاس حذف شد.');
    }
    public function addSubject(Request $request, SchoolClass $class)
    {
        $request->validate([
            'subject_id'           => 'required|exists:subjects,id',
            'default_start_period' => 'required|integer|between:1,4',
        ]);

        $subject = Subject::findOrFail($request->subject_id);

        $class->classSubjects()->firstOrCreate(
            ['subject_id' => $subject->id],
            [
                'days_of_week'         => $subject->default_days,
                'default_start_period' => (int) $request->default_start_period,
            ]
        );

        return redirect()->route('admin.classes.show', $class)
            ->with('success', 'درس به کلاس اضافه شد.');
    }

    public function removeSubject(SchoolClass $class, ClassSubject $classSubject)
    {
        abort_unless($classSubject->school_class_id === $class->id, 404);
        $classSubject->delete();

        return redirect()->route('admin.classes.show', $class)
            ->with('success', 'درس از کلاس حذف شد.');
    }
    public function updateSubjectDays(Request $request, SchoolClass $class, ClassSubject $classSubject)
    {
        abort_unless($classSubject->school_class_id === $class->id, 404);

        $request->validate([
            'days_of_week'         => 'nullable|array',
            'days_of_week.*'       => 'integer|between:0,6',
            'default_start_period' => 'required|integer|between:1,4',
        ]);

        $classSubject->update([
            'days_of_week'         => $request->days_of_week ?? [],
            'default_start_period' => (int) $request->default_start_period,
        ]);

        return redirect()->route('admin.classes.show', $class)
            ->with('success', 'تنظیمات درس به‌روزرسانی شد.');
    }
    public function reportFull(Request $request, SchoolClass $class)
    {
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('success', 'ابتدا یک سال تحصیلی «جاری» تعریف کنید.');
        }

        $service = new StudentReportService();

        $quickFilter = $request->input('quick');
        $fromInput   = $request->input('from');
        $toInput     = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);

        // جدول اول (کاور) — مرتب بر اساس رتبه
        $ranking = $service->getClassRanking($class, $fromDate, $toDate);
        $summary = $service->getClassSummary($class, $fromDate, $toDate);

        $classSubjects = $class->classSubjects()->with('subject')->get();
        $subjectSummaries = $service->getClassSubjectSummaries($class, $fromDate, $toDate);

        $subjects = $classSubjects->pluck('subject.name')
            ->filter()
            ->values();

        // نقشه رتبه بر اساس student_id برای دسترسی سریع
        $rankByStudent = $ranking->keyBy(fn($r) => $r['student']->id);

        // حلقه دانش‌آموزان — بر اساس حروف الفبا
        $students = $class->students()->orderBy('name')->get();

        $studentDetails = $students->map(function ($student) use ($service, $fromDate, $toDate, $rankByStudent, $class) {
            $row = $rankByStudent->get($student->id);

            $bySubject         = $service->getStatsByClassSubject($student, $fromDate, $toDate);
            $points            = $service->getPointsSummary($student, $fromDate, $toDate);
            $absences          = $service->getAbsenceDetails($student, $fromDate, $toDate);
            $examResults       = $service->getExamResultsForStudent($student);
            $subjectSummary    = $service->getSubjectSummary($student, $fromDate, $toDate);
            $exerciseResults   = $service->getExerciseResultsForStudent($student);
            $groupedAttendance = $service->getGroupedAttendanceByDate($student, $class, $fromDate, $toDate); // ← جدید

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
                'groupedAttendance' => $groupedAttendance, // ← جدید
                'status'            => $status,
            ];
        });

        return view('admin.classes.full-report', compact(
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
        ));
    }
}
