<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id', 'student_id', 'attempt_number', 'started_at', 'finished_at',
        'total_questions', 'total_correct', 'score', 'status', 'tab_switches',
        'teacher_score', 'is_approved',
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

    public function isExpired(): bool
    {
        if ($this->status !== 'in_progress') return true;
        $endsAt = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
        return now()->gt($endsAt);
    }

    public function remainingSeconds(): int
    {
        $endsAt = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);
        return max(0, $endsAt->timestamp - now()->timestamp);
    }

    public function getFinalScoreAttribute()
    {
        return $this->is_approved && $this->teacher_score !== null
            ? $this->teacher_score
            : $this->score;
    }
}
