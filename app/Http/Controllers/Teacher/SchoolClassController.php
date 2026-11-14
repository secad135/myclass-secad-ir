<?php

namespace App\Http\Controllers\Teacher;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Services\ClassReportService;
use Illuminate\Http\Request;

class SchoolClassController extends BaseController
{
    public function index()
    {
        $classes = SchoolClass::forTeacher($this->teacherId())
            ->with(['school', 'academicYear', 'grade', 'teacher'])
            ->withCount(['students'])
            ->latest()
            ->get();

        return view('admin.classes.index', compact('classes'));
    }

    public function show(SchoolClass $class)
    {
        $this->assertOwnsClass($class);

        $class->load(['school', 'academicYear', 'grade', 'teacher', 'students', 'classSubjects.subject', 'classSubjects.teacher']);

        $enrolledIds = $class->students->pluck('id')->toArray();
        $availableStudents = User::students()
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->get();

        $linkedSubjectIds = $class->classSubjects->pluck('subject_id')->toArray();
        $availableSubjects = Subject::whereNotIn('id', $linkedSubjectIds)
            ->orderBy('name')
            ->get();

        $teachers = collect();

        return view('admin.classes.show', compact('class', 'availableStudents', 'availableSubjects', 'teachers'));
    }

    public function report(Request $request, SchoolClass $class)
    {
        $this->assertOwnsClass($class);

        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'ابتدا مدیر باید یک سال تحصیلی «جاری» تعریف کند.');
        }

        $data = (new ClassReportService())->reportData($request, $class, $currentYear, $this->teacherId());

        return view('admin.classes.report', $data);
    }

    public function reportFull(Request $request, SchoolClass $class)
    {
        $this->assertOwnsClass($class);

        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('teacher.dashboard')
                ->with('error', 'ابتدا مدیر باید یک سال تحصیلی «جاری» تعریف کند.');
        }

        $data = (new ClassReportService())->fullReportData($request, $class, $currentYear, $this->teacherId());

        return view('admin.classes.full-report', $data);
    }

    protected function assertOwnsClass(SchoolClass $class): void
    {
        $owns = SchoolClass::forTeacher($this->teacherId())
            ->whereKey($class->getKey())
            ->exists();

        abort_unless($owns, 403);
    }
}
