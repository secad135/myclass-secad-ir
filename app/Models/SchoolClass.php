<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'name',
        'school_id',
        'academic_year_id',
        'grade_id',
        'teacher_id',
    ];

    // --- روابط فعلی ---

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'school_class_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'enrollments',
            'school_class_id',
            'student_id'
        )->withPivot('status', 'enrolled_at', 'notes')->withTimestamps();
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->whereHas('classSubjects', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        });
    }

    // --- روابط جدید (که جا افتاده بودند) ---

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