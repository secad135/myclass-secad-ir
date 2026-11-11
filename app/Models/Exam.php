<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'class_subject_id', 'title', 'description', 'question_count',
        'duration_minutes', 'start_at', 'end_at', 'shuffle_options',
        'show_result_immediately', 'max_attempts', 'is_active',
    ];

    protected $casts = [
        'start_at'               => 'datetime',
        'end_at'                 => 'datetime',
        'shuffle_options'        => 'boolean',
        'show_result_immediately'=> 'boolean',
        'is_active'              => 'boolean',
    ];

    public function classSubject()
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'exam_topics')->withTimestamps();
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isOpenNow(): bool
    {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) return false;
        if ($this->end_at && $now->gt($this->end_at)) return false;
        return true;
    }

    public function statusLabel(): array
    {
        if (!$this->is_active) {
            return ['label' => 'غیرفعال', 'color' => 'secondary'];
        }
        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) {
            return ['label' => 'در انتظار شروع', 'color' => 'info'];
        }
        if ($this->end_at && $now->gt($this->end_at)) {
            return ['label' => 'پایان یافته', 'color' => 'secondary'];
        }
        return ['label' => 'باز', 'color' => 'success'];
    }
}
