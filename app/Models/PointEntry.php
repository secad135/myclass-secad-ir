<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointEntry extends Model
{
    protected $fillable = [
        'student_id', 'class_subject_id', 'point_category_id',
        'points', 'note', 'entry_date', 'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'points'     => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classSubject()
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function category()
    {
        return $this->belongsTo(PointCategory::class, 'point_category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}