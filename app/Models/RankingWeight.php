<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingWeight extends Model
{
    public const COMPONENTS = [
        'exams'      => 'آزمون‌ها',
        'exercises'  => 'تمرین‌ها',
        'attendance' => 'حضور و غیاب',
        'manual'     => 'امتیاز دستی',
    ];

    protected $fillable = [
        'teacher_id',
        'component',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * وزن پیش‌فرض هر مؤلفه بر اساس فایل config (به صورت درصد).
     *
     * @return array<string, float>
     */
    public static function defaultPercents(): array
    {
        $weights = config('ranking.weights', [
            'exams'      => 0.40,
            'exercises'  => 0.25,
            'attendance' => 0.20,
            'manual'     => 0.15,
        ]);

        $percents = [];

        foreach (array_keys(self::COMPONENTS) as $component) {
            $percents[$component] = round(((float) ($weights[$component] ?? 0)) * 100, 2);
        }

        return $percents;
    }

    /**
     * وزن مؤثر یک معلم به صورت کسری (۰ تا ۱). ردیف‌های ثبت‌شده
     * جای مقدار پیش‌فرض همان مؤلفه را می‌گیرند.
     *
     * @return array<string, float>
     */
    public static function weightsForTeacher(?int $teacherId): array
    {
        $percents = self::percentsForTeacher($teacherId);

        return array_map(fn (float $percent) => $percent / 100, $percents);
    }

    /**
     * وزن مؤثر یک معلم به صورت درصد (۰ تا ۱۰۰).
     *
     * @return array<string, float>
     */
    public static function percentsForTeacher(?int $teacherId): array
    {
        $percents = self::defaultPercents();

        if ($teacherId === null) {
            return $percents;
        }

        $overrides = static::query()
            ->where('teacher_id', $teacherId)
            ->pluck('weight', 'component');

        foreach (array_keys(self::COMPONENTS) as $component) {
            if ($overrides->has($component)) {
                $percents[$component] = (float) $overrides->get($component);
            }
        }

        return $percents;
    }

    /**
     * آیا معلم برای این مؤلفه وزن سفارشی ثبت کرده است؟
     */
    public static function hasOverride(int $teacherId, string $component): bool
    {
        return static::query()
            ->where('teacher_id', $teacherId)
            ->where('component', $component)
            ->exists();
    }

    public static function label(string $component): string
    {
        return self::COMPONENTS[$component] ?? $component;
    }
}
