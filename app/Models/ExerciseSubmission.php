<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseSubmission extends Model
{
    protected $fillable = [
        'exercise_id', 'student_id', 'content', 'code_content', 'file_path',
        'submitted_at', 'is_late', 'score', 'feedback', 'graded_at', 'graded_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
        'is_late'      => 'boolean',
        'score'        => 'float',
    ];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }
}
