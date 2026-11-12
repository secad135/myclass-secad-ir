<?php

namespace App\Http\Controllers\Teacher;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;

class DashboardController extends BaseController
{
    public function index()
    {
        $classSubjectIds = $this->ownClassSubjectIds();
        $classIds        = $this->ownClassIds();

        $stats = [
            'class_subjects' => count($classSubjectIds),
            'classes'        => count($classIds),
            'students'       => $this->ownStudentQuery()->count(),
            'pending'        => empty($classSubjectIds)
                ? 0
                : ExerciseSubmission::whereNull('graded_at')
                    ->whereHas('exercise', fn ($q) => $q->whereIn('class_subject_id', $classSubjectIds))
                    ->count(),
        ];

        $currentYear = AcademicYear::where('is_current', true)->first();

        $classSubjects = ClassSubject::with(['subject', 'schoolClass.school'])
            ->whereIn('id', $classSubjectIds)
            ->get();

        $recentExams = empty($classSubjectIds) ? collect() : Exam::with('classSubject.subject')
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            })
            ->latest()
            ->take(3)
            ->get();

        $recentExercises = empty($classSubjectIds) ? collect() : Exercise::with('classSubject.subject')
            ->whereIn('class_subject_id', $classSubjectIds)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('deadline_at')->orWhere('deadline_at', '>=', now());
            })
            ->latest()
            ->take(3)
            ->get();

        return view('teacher.dashboard', compact(
            'stats',
            'currentYear',
            'classSubjects',
            'recentExams',
            'recentExercises'
        ));
    }
}
