<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    protected $fillable = [
        'school_class_id', 'subject_id', 'teacher_id',
        'days_of_week', 'start_date', 'end_date',
        'default_start_period',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'default_start_period' => 'integer',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function exercises()
    {
        return $this->hasMany(Exercise::class);
    }

    // آیا امروز این درس برگزار می‌شود؟
    public function isScheduledOn(string $date): bool
    {
        $days = $this->days_of_week ?? $this->subject->default_days ?? [];
        // روز هفته به فارسی: 0=شنبه ... 6=جمعه
        $phpDay = (int) date('w', strtotime($date)); // 0=Sun, 6=Sat
        $persianDay = ($phpDay + 1) % 7;
        return in_array($persianDay, $days);
    }
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subjects'
        )->withPivot('id', 'days_of_week', 'start_date', 'end_date')->withTimestamps();
    }
}