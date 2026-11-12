<?php

namespace App\Http\Controllers\Teacher;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;

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

    protected function assertOwnsClass(SchoolClass $class): void
    {
        $owns = SchoolClass::forTeacher($this->teacherId())
            ->whereKey($class->getKey())
            ->exists();

        abort_unless($owns, 403);
    }
}
