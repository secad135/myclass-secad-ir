<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id', 'student_id', 'attempt_number', 'started_at', 'finished_at',
        'total_questions', 'total_correct', 'score', 'status', 'tab_switches',
        'teacher_score', 'is_approved', 'session_id', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'score'       => 'float',
        'is_approved' => 'boolean',
        'teacher_score' => 'float',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(ExamAttemptAnswer::class);
    }

    public function events()
    {
        return $this->hasMany(ExamEvent::class)->orderBy('occurred_at');
    }

    public function endsAt(): \Carbon\Carbon
    {
        $endsAt = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);

        if ($this->exam->end_at && $this->exam->end_at->lt($endsAt)) {
            $endsAt = $this->exam->end_at->copy();
        }

        return $endsAt;
    }

    public function isExpired(): bool
    {
        if ($this->status !== 'in_progress') return true;
        return now()->gt($this->endsAt());
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->endsAt()->timestamp - now()->timestamp);
    }

    public function canReviewAnswers(): bool
    {
        $exam = $this->exam;

        if (!$exam->show_result_immediately) {
            return false;
        }

        if ($exam->max_attempts <= 1 || $this->is_approved) {
            return true;
        }

        if ($exam->end_at && now()->gt($exam->end_at)) {
            return true;
        }

        return $exam->attempts()
            ->where('student_id', $this->student_id)
            ->whereIn('status', ['submitted', 'expired'])
            ->count() >= $exam->max_attempts;
    }

    public function getFinalScoreAttribute()
    {
        return $this->is_approved && $this->teacher_score !== null
            ? $this->teacher_score
            : $this->score;
    }
}
