<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExamService
{
    /**
     * آزمون‌های در دسترس برای یک دانش‌آموز
     */
    public function getAvailableExamsForStudent(User $student)
    {
        $classIds = $student->classes()->pluck('school_classes.id')->toArray();

        if (empty($classIds)) {
            return collect();
        }

        return Exam::with(['classSubject.subject', 'classSubject.schoolClass'])
            ->whereHas('classSubject', fn($q) => $q->whereIn('school_class_id', $classIds))
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * بررسی امکان شرکت در آزمون
     */
    public function canAttempt(Exam $exam, User $student): array
    {
        if (!$exam->is_active) {
            return ['ok' => false, 'reason' => 'این آزمون غیرفعال است.'];
        }
        if (!$exam->isOpenNow()) {
            return ['ok' => false, 'reason' => 'این آزمون در بازه دسترسی نیست.'];
        }

        // آیا آزمون در حال انجام دارد؟
        $inProgress = $exam->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return [
                'ok'               => false,
                'reason'           => 'شما یک آزمون در حال انجام دارید.',
                'in_progress_id'   => $inProgress->id,
            ];
        }

        $attemptsCount = $exam->attempts()
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->count();

        if ($attemptsCount >= $exam->max_attempts) {
            return [
                'ok'     => false,
                'reason' => "شما به حداکثر تعداد تلاش ({$exam->max_attempts}) رسیده‌اید.",
            ];
        }

        return ['ok' => true, 'attempt_number' => $attemptsCount + 1];
    }

    /**
     * ساخت یک attempt جدید با سوالات تصادفی
     */
    public function createAttempt(Exam $exam, User $student): ExamAttempt
    {
        $check = $this->canAttempt($exam, $student);
        if (!$check['ok']) {
            throw new \Exception($check['reason']);
        }

        $topicIds = $exam->topics->pluck('id');

        $questions = Question::active()
            ->whereIn('topic_id', $topicIds)
            ->inRandomOrder()
            ->limit($exam->question_count)
            ->get();

        if ($questions->count() < $exam->question_count) {
            throw new \Exception("سوالات کافی وجود ندارد. فقط {$questions->count()} سوال فعال در مباحث انتخابی هست.");
        }

        return DB::transaction(function () use ($exam, $student, $questions) {
            $attemptNumber = $exam->attempts()
                ->where('student_id', $student->id)
                ->count() + 1;

            $attempt = ExamAttempt::create([
                'exam_id'         => $exam->id,
                'student_id'      => $student->id,
                'attempt_number'  => $attemptNumber,
                'started_at'      => now(),
                'total_questions' => $questions->count(),
                'status'          => 'in_progress',
            ]);

            foreach ($questions as $question) {
                $options = $question->options;

                if ($exam->shuffle_options) {
                    $options = $options->shuffle();
                }

                ExamAttemptAnswer::create([
                    'exam_attempt_id'    => $attempt->id,
                    'question_id'        => $question->id,
                    'selected_option_id' => null,
                    'options_order'      => $options->pluck('id')->toArray(),
                    'is_correct'         => false,
                ]);
            }

            return $attempt;
        });
    }

    /**
     * ثبت پاسخ‌ها و محاسبه نمره
     */
    public function submitAttempt(ExamAttempt $attempt, array $answers, string $status = 'submitted'): ExamAttempt
    {
        if ($attempt->status !== 'in_progress') {
            throw new \Exception('این آزمون قبلاً ثبت شده است.');
        }

        return DB::transaction(function () use ($attempt, $answers, $status) {
            $correctCount = 0;

            foreach ($attempt->answers as $answer) {
                $selectedId = $answers[$answer->question_id] ?? null;

                $isCorrect = false;
                if ($selectedId) {
                    $correctOption = $answer->question->options->firstWhere('is_correct', true);
                    $isCorrect = $correctOption && (int) $correctOption->id === (int) $selectedId;
                }

                $answer->update([
                    'selected_option_id' => $selectedId,
                    'is_correct'         => $isCorrect,
                ]);

                if ($isCorrect) {
                    $correctCount++;
                }
            }

            $score = $attempt->total_questions > 0
                ? round(($correctCount / $attempt->total_questions) * 20, 2)
                : 0;

            $attempt->update([
                'finished_at'     => now(),
                'total_correct'   => $correctCount,
                'score'           => $score,
                'status'          => $status,
            ]);

            return $attempt->fresh();
        });
    }

    /**
     * خلاصه نتایج آزمون برای معلم
     */
    public function getExamResultsSummary(Exam $exam): array
    {
        $students = $exam->classSubject->schoolClass->students()->orderBy('name')->get();

        $rows = $students->map(function ($student) use ($exam) {
            $attempts = $exam->attempts()
                ->where('student_id', $student->id)
                ->get()
                ->sortByDesc('final_score')
                ->values();

            $best = $attempts->first();
            $attempted = $attempts->whereIn('status', ['submitted', 'expired'])->count();
            $inProgress = $attempts->where('status', 'in_progress')->count();

            return [
                'student'      => $student,
                'attempts'     => $attempts,
                'attempted'    => $attempted,
                'in_progress'  => $inProgress,
                'best_score'   => $best?->final_score ?? null,
                'best_attempt' => $best,
                'last_attempt' => $attempts->sortByDesc('finished_at')->first(),
            ];
        });

        return [
            'rows'         => $rows,
            'students_count' => $students->count(),
            'attempted_count' => $rows->where('attempted', '>', 0)->count(),
            'avg_score'    => round($rows->whereNotNull('best_score')->avg('best_score') ?? 0, 2),
            'highest_score' => $rows->whereNotNull('best_score')->max('best_score') ?? 0,
            'lowest_score' => $rows->whereNotNull('best_score')->min('best_score') ?? 0,
        ];
    }

    /**
     * تحلیل سوال‌به‌سوال
     */
    public function getQuestionAnalysis(Exam $exam): array
    {
        // جمع‌آوری همه پاسخ‌های ثبت‌شده این آزمون
        $attemptIds = $exam->attempts()
            ->whereIn('status', ['submitted', 'expired'])
            ->pluck('id');

        if ($attemptIds->isEmpty()) {
            return [];
        }

        $answers = \App\Models\ExamAttemptAnswer::whereIn('exam_attempt_id', $attemptIds)
            ->with('question.options')
            ->get()
            ->groupBy('question_id');

        $analysis = [];

        foreach ($answers as $questionId => $group) {
            $question = $group->first()->question;
            if (!$question) continue;

            $total      = $group->count();
            $correct    = $group->where('is_correct', true)->count();
            $unanswered = $group->whereNull('selected_option_id')->count();
            $wrong      = $total - $correct - $unanswered;

            $analysis[] = [
                'question'      => $question,
                'total'         => $total,
                'correct'       => $correct,
                'wrong'         => $wrong,
                'unanswered'    => $unanswered,
                'correct_rate'  => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
            ];
        }

        // مرتب‌سازی بر اساس سختی (نرخ صحیح کم‌تر = سخت‌تر)
        usort($analysis, fn($a, $b) => $a['correct_rate'] <=> $b['correct_rate']);

        return $analysis;
    }

    /**
     * بستن خودکار آزمون‌هایی که زمانشان تمام شده اما ثبت نشده‌اند
     */
    public function expireStaleAttempts(): void
    {
        $staleAttempts = ExamAttempt::where('status', 'in_progress')
            ->with('exam')
            ->get()
            ->filter(fn($attempt) => $attempt->isExpired());

        foreach ($staleAttempts as $attempt) {
            $this->submitAttempt($attempt, [], 'expired');
        }
    }
}
