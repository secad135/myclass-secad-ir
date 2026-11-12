<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Services\ClassReportService;
use App\Services\ExamService;

class SchoolClassController extends Controller
{
    public function report(Request $request, SchoolClass $class)
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('success', 'ابتدا یک سال تحصیلی «جاری» تعریف کنید.');
        }

        $data = (new ClassReportService())->reportData($request, $class, $currentYear);

        return view('admin.classes.report', $data);
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
        $teachers = User::teachers()->orderBy('name')->get();

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

        $teachers = User::teachers()->orderBy('name')->get();

        return view('admin.classes.show', compact('class', 'availableStudents', 'availableSubjects', 'teachers'));
    }

    public function edit(SchoolClass $class)
    {
        $schools  = School::orderBy('name')->get();
        $years    = AcademicYear::orderByDesc('is_current')->orderByDesc('title')->get();
        $grades   = Grade::orderBy('name')->get();
        $teachers = User::teachers()->orderBy('name')->get();
        if ($class->teacher && !$class->teacher->is_teacher) {
            $teachers->prepend($class->teacher);
        }

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
            'teacher_id'           => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_teacher', true)),
            ],
        ]);

        $subject = Subject::findOrFail($request->subject_id);

        $class->classSubjects()->firstOrCreate(
            ['subject_id' => $subject->id],
            [
                'days_of_week'         => $subject->default_days,
                'default_start_period' => (int) $request->default_start_period,
                'teacher_id'           => $request->teacher_id,
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

    public function updateSubjectTeacher(Request $request, SchoolClass $class, ClassSubject $classSubject)
    {
        abort_unless($classSubject->school_class_id === $class->id, 404);

        $request->validate([
            'teacher_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_teacher', true)),
            ],
        ], [
            'teacher_id.exists' => 'معلم انتخاب‌شده معتبر نیست.',
        ]);

        $classSubject->update(['teacher_id' => $request->teacher_id]);

        return redirect()->route('admin.classes.show', $class)
            ->with('success', 'معلم درس به‌روزرسانی شد.');
    }
    public function reportFull(Request $request, SchoolClass $class)
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('success', 'ابتدا یک سال تحصیلی «جاری» تعریف کنید.');
        }

        $data = (new ClassReportService())->fullReportData($request, $class, $currentYear);

        return view('admin.classes.full-report', $data);
    }
}
