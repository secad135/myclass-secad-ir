<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamEvent extends Model
{
    protected $fillable = [
        'exam_attempt_id', 'type', 'reason', 'ip_address',
        'user_agent', 'metadata', 'occurred_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'occurred_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
