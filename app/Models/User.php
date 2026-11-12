<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_admin', 'is_teacher', 'is_student', 
        'is_school_manager', 'school_id', 'initial_password', 'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'is_admin'             => 'boolean',
            'is_teacher'           => 'boolean',
            'is_student'           => 'boolean',
            'is_school_manager'    => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // Scopes - برای فیلتر راحت‌تر
    public function scopeStudents($query)
    {
        return $query->where('is_student', true);
    }

    public function scopeTeachers($query)
    {
        return $query->where('is_teacher', true);
    }

    public function classSubjectsAsTeacher()
    {
        return $this->hasMany(ClassSubject::class, 'teacher_id');
    }

    public function homeroomClasses()
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function createdExams()
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function createdExercises()
    {
        return $this->hasMany(Exercise::class, 'created_by');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // رابطه با ثبت‌نام‌ها (بعداً استفاده می‌شود)
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function classes()
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'enrollments',
            'student_id',
            'school_class_id'
        )->withPivot('status', 'enrolled_at', 'notes')->withTimestamps();
    }
        /**
     * ارسال نوتیفیکیشن اختصاصی برای بازیابی رمز عبور
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
