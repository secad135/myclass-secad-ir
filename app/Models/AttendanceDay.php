<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDay extends Model
{
    protected $fillable = [
        'class_subject_id', 'date', 'start_period',
        'sessions_count', 'is_started', 'started_at',
    ];

    protected $casts = [
        'date' => 'date',
        'started_at' => 'datetime',
        'is_started' => 'boolean',
        'sessions_count' => 'integer',
        'start_period' => 'integer',
    ];

    public function classSubject()
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendancesByStudent()
    {
        return $this->attendances->groupBy('student_id');
    }
}
