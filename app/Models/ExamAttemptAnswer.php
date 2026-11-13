<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttemptAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id', 'question_id', 'selected_option_id',
        'correct_option_id', 'options_order', 'is_correct',
        'first_answered_at', 'last_answered_at',
    ];

    protected $casts = [
        'options_order'     => 'array',
        'is_correct'        => 'boolean',
        'first_answered_at' => 'datetime',
        'last_answered_at'  => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    public function correctOption()
    {
        return $this->belongsTo(QuestionOption::class, 'correct_option_id');
    }
}
