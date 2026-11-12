<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exercise;
use App\Models\Exam;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BaseController extends Controller
{
    protected function teacherId(): int
    {
        return (int) auth()->id();
    }

    protected function ownClassSubjectQuery(): Builder
    {
        return ClassSubject::query()->where('teacher_id', $this->teacherId());
    }

    protected function ownClassSubjectIds(): array
    {
        return $this->ownClassSubjectQuery()->pluck('id')->all();
    }

    protected function ownClassIds(): array
    {
        return $this->ownClassSubjectQuery()->pluck('school_class_id')->unique()->values()->all();
    }

    protected function ownSubjectIds(): array
    {
        return $this->ownClassSubjectQuery()->pluck('subject_id')->unique()->values()->all();
    }

    protected function ownTopicIds(): array
    {
        return Topic::whereIn('subject_id', $this->ownSubjectIds())->pluck('id')->all();
    }

    protected function ownsSubject(int $subjectId): bool
    {
        return in_array($subjectId, array_map('intval', $this->ownSubjectIds()), true);
    }

    protected function assertOwnsSubject(int $subjectId): void
    {
        abort_unless($this->ownsSubject($subjectId), 403);
    }

    protected function assertOwnsTopic(Topic $topic): void
    {
        $this->assertOwnsSubject((int) $topic->subject_id);
    }

    protected function assertOwnsTopicId(int $topicId): void
    {
        abort_unless(
            in_array($topicId, array_map('intval', $this->ownTopicIds()), true),
            403
        );
    }

    protected function ownClassSubjectIdsForOwnClasses(): array
    {
        $classIds = $this->ownClassIds();

        if (empty($classIds)) {
            return [];
        }

        return ClassSubject::whereIn('school_class_id', $classIds)->pluck('id')->all();
    }

    protected function ownStudentQuery(): Builder
    {
        $classIds = $this->ownClassIds();

        return User::students()
            ->when(empty($classIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->whereHas('enrollments', function ($q) use ($classIds) {
                $q->whereIn('school_class_id', $classIds);
            });
    }

    protected function assertOwnsClassSubject(ClassSubject $classSubject): void
    {
        abort_unless((int) $classSubject->teacher_id === $this->teacherId(), 403);
    }

    protected function assertOwnsExam(Exam $exam): void
    {
        $exam->loadMissing('classSubject');
        abort_unless((int) $exam->classSubject?->teacher_id === $this->teacherId(), 403);
    }

    protected function assertOwnsExercise(Exercise $exercise): void
    {
        $exercise->loadMissing('classSubject');
        abort_unless((int) $exercise->classSubject?->teacher_id === $this->teacherId(), 403);
    }

    protected function assertOwnsStudent(User $student): void
    {
        abort_unless($student->is_student, 404);

        $owns = $student->enrollments()
            ->whereIn('school_class_id', $this->ownClassIds())
            ->exists();

        abort_unless($owns, 403);
    }
}
