<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['topic_id', 'text', 'code_snippet', 'difficulty', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function correctOption()
    {
        return $this->hasOne(QuestionOption::class)->where('is_correct', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function difficultyLabel(): string
    {
        return match($this->difficulty) {
            'easy'   => 'آسان',
            'medium' => 'متوسط',
            'hard'   => 'سخت',
            default  => $this->difficulty,
        };
    }

    public function difficultyColor(): string
    {
        return match($this->difficulty) {
            'easy'   => 'success',
            'medium' => 'warning',
            'hard'   => 'danger',
            default  => 'secondary',
        };
    }
}