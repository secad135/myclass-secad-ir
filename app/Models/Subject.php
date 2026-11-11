<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'sessions_per_day', 'default_days',
    ];

    protected $casts = [
        'default_days' => 'array',
        'sessions_per_day' => 'integer',
    ];

    // رابطه با کلاس‌ها (بعداً استفاده می‌شود)
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    // نام فارسی نوع درس
    public function typeLabel(): string
    {
        return $this->type === 'workshop' ? 'کارگاه' : 'عادی';
    }

    // نام فارسی روزها
    public static function dayNames(): array
    {
        return [
            0 => 'شنبه',
            1 => 'یکشنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنجشنبه',
            6 => 'جمعه',
        ];
    }

    public function daysLabel(): string
    {
        if (empty($this->default_days)) {
            return '—';
        }
        $names = self::dayNames();
        return collect($this->default_days)
            ->map(fn($d) => $names[$d] ?? '')
            ->filter()
            ->implode('، ');
    }
}