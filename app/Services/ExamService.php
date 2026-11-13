<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamEvent;
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
        $classIds = $student->classes()
            ->wherePivot('status', 'active')
            ->pluck('school_classes.id')
            ->toArray();

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

        $classIds = $student->classes()
            ->wherePivot('status', 'active')
            ->pluck('school_classes.id');

        if (!$exam->classSubject || !$classIds->contains($exam->classSubject->school_class_id)) {
            return ['ok' => false, 'reason' => 'شما به این آزمون دسترسی ندارید.'];
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

        $otherInProgress = ExamAttempt::where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->where('exam_id', '!=', $exam->id)
            ->exists();

        if ($otherInProgress) {
            return ['ok' => false, 'reason' => 'شما یک آزمون دیگر در حال انجام دارید.'];
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
    public function createAttempt(Exam $exam, User $student, array $context = []): ExamAttempt
    {
        return DB::transaction(function () use ($exam, $student, $context) {
            // قفل دانش‌آموز از شروع هم‌زمان چند آزمون جلوگیری می‌کند
            DB::table('users')->where('id', $student->id)->lockForUpdate()->first();

            // قفل رکورد آزمون از ساخت هم‌زمان چند تلاش جلوگیری می‌کند
            $lockedExam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();

            $check = $this->canAttempt($lockedExam, $student);
            if (!$check['ok']) {
                throw new \Exception($check['reason']);
            }

            $topicIds = $lockedExam->topics->pluck('id');

            $previousQuestionIds = ExamAttemptAnswer::query()
                ->whereHas('attempt', function ($q) use ($lockedExam, $student) {
                    $q->where('exam_id', $lockedExam->id)
                        ->where('student_id', $student->id)
                        ->whereIn('status', ['submitted', 'expired']);
                })
                ->pluck('question_id')
                ->unique()
                ->values()
                ->toArray();

            $baseQuery = Question::active()->whereIn('topic_id', $topicIds);

            $questions = (clone $baseQuery)
                ->whereNotIn('id', $previousQuestionIds)
                ->inRandomOrder()
                ->limit($lockedExam->question_count)
                ->get();

            // اگر سوال تکراری‌نشده کافی نبود، بقیه از مخزن کامل تکمیل می‌شود
            if ($questions->count() < $lockedExam->question_count) {
                $extra = (clone $baseQuery)
                    ->whereNotIn('id', $questions->pluck('id')->toArray())
                    ->inRandomOrder()
                    ->limit($lockedExam->question_count - $questions->count())
                    ->get();

                $questions = $questions->merge($extra);
            }

            if ($questions->count() < $lockedExam->question_count) {
                throw new \Exception("سوالات کافی وجود ندارد. فقط {$questions->count()} سوال فعال در مباحث انتخابی هست.");
            }

            $attemptNumber = $lockedExam->attempts()
                ->where('student_id', $student->id)
                ->count() + 1;

            $attempt = ExamAttempt::create([
                'exam_id'         => $lockedExam->id,
                'student_id'      => $student->id,
                'attempt_number'  => $attemptNumber,
                'started_at'      => now(),
                'total_questions' => $questions->count(),
                'status'          => 'in_progress',
                'session_id'      => $context['session_id'] ?? null,
                'ip_address'      => $context['ip_address'] ?? null,
                'user_agent'      => $context['user_agent'] ?? null,
            ]);

            foreach ($questions as $question) {
                $options = $question->options;

                if ($lockedExam->shuffle_options) {
                    $options = $options->shuffle();
                }

                ExamAttemptAnswer::create([
                    'exam_attempt_id'    => $attempt->id,
                    'question_id'        => $question->id,
                    'selected_option_id' => null,
                    'correct_option_id'  => $question->options->firstWhere('is_correct', true)?->id,
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
    public function submitAttempt(ExamAttempt $attempt, array $answers, string $status = 'submitted', array $context = []): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $answers, $status, $context) {
            $locked = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'in_progress') {
                throw new \Exception('این آزمون قبلاً ثبت شده است.');
            }

            $locked->load(['answers.question.options']);
            $correctCount = 0;

            foreach ($locked->answers as $answer) {
                $hasSubmittedAnswer = array_key_exists($answer->question_id, $answers);
                $previousSelectedId = $answer->selected_option_id;
                $selectedId = $hasSubmittedAnswer
                    ? $answers[$answer->question_id]
                    : $answer->selected_option_id;

                $selectedId = $this->validSelectedOptionId($answer, $selectedId);
                $correctId = $answer->correct_option_id
                    ?: $answer->question->options->firstWhere('is_correct', true)?->id;

                $isCorrect = $selectedId && $correctId
                    && (int) $selectedId === (int) $correctId;

                $updates = [
                    'selected_option_id' => $selectedId,
                    'is_correct'         => $isCorrect,
                ];

                $answerChanged = $hasSubmittedAnswer
                    && (int) $selectedId !== (int) $previousSelectedId;

                if ($selectedId && ($answerChanged || !$answer->first_answered_at)) {
                    if (!$answer->first_answered_at) {
                        $updates['first_answered_at'] = now();
                    }
                    $updates['last_answered_at'] = now();
                } elseif (!$selectedId) {
                    $updates['first_answered_at'] = null;
                    $updates['last_answered_at'] = null;
                }

                $answer->update($updates);

                if ($isCorrect) {
                    $correctCount++;
                }
            }

            $score = $locked->total_questions > 0
                ? round(($correctCount / $locked->total_questions) * 20, 2)
                : 0;

            $locked->update([
                'finished_at'     => now(),
                'total_correct'   => $correctCount,
                'score'           => $score,
                'status'          => $status,
            ]);

            ExamEvent::create([
                'exam_attempt_id' => $locked->id,
                'type'            => $status === 'expired' ? 'expired' : 'submitted',
                'ip_address'      => $context['ip_address'] ?? null,
                'user_agent'      => $context['user_agent'] ?? null,
                'metadata'        => [
                    'score'   => $score,
                    'correct' => $correctCount,
                    'total'   => $locked->total_questions,
                ],
                'occurred_at'     => now(),
            ]);

            return $locked->fresh();
        });
    }

    /**
     * ذخیره خودکار پاسخ‌ها بدون ثبت نهایی آزمون
     */
    public function saveAnswers(ExamAttempt $attempt, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            $locked = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'in_progress') {
                throw new \Exception('این آزمون دیگر در حال انجام نیست.');
            }
            if ($locked->isExpired()) {
                throw new \Exception('زمان آزمون به پایان رسیده است.');
            }

            $locked->load(['answers.question.options']);

            foreach ($locked->answers as $answer) {
                if (!array_key_exists($answer->question_id, $answers)) {
                    continue;
                }

                $previousSelectedId = $answer->selected_option_id;
                $selectedId = $this->validSelectedOptionId($answer, $answers[$answer->question_id]);
                $correctId = $answer->correct_option_id
                    ?: $answer->question->options->firstWhere('is_correct', true)?->id;

                $updates = [
                    'selected_option_id' => $selectedId,
                    'is_correct'         => $selectedId && $correctId
                        && (int) $selectedId === (int) $correctId,
                ];

                $answerChanged = (int) $selectedId !== (int) $previousSelectedId;

                if ($selectedId && ($answerChanged || !$answer->first_answered_at)) {
                    if (!$answer->first_answered_at) {
                        $updates['first_answered_at'] = now();
                    }
                    $updates['last_answered_at'] = now();
                } elseif (!$selectedId) {
                    $updates['first_answered_at'] = null;
                    $updates['last_answered_at'] = null;
                }

                $answer->update($updates);
            }

            $locked->touch();

            return $locked->fresh();
        });
    }

    /**
     * فقط گزینه‌ای معتبر است که متعلق به همان سؤال و همان ترتیب ذخیره‌شده باشد.
     */
    private function validSelectedOptionId(ExamAttemptAnswer $answer, $selectedId): ?int
    {
        if ($selectedId === null || $selectedId === '') {
            return null;
        }

        $selectedId = (int) $selectedId;
        $order = array_map('intval', $answer->options_order ?? []);

        if (!in_array($selectedId, $order, true)) {
            return null;
        }

        return $answer->question->options->firstWhere('id', $selectedId) ? $selectedId : null;
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
