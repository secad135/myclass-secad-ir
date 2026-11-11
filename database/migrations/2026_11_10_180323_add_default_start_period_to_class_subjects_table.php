<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_subjects', function (Blueprint $table) {
            $table->unsignedTinyInteger('default_start_period')->default(1)->after('days_of_week');
        });

        // خودکار تشخیص: هر روز، درس‌ها را بر اساس تعداد جلسه (کمتر اول) مرتب کن
        $classSubjects = \App\Models\ClassSubject::with('subject')->get();
        $byClassDay = [];

        foreach ($classSubjects as $cs) {
            foreach (($cs->days_of_week ?? []) as $day) {
                $byClassDay[$cs->school_class_id . '|' . $day][] = $cs;
            }
        }

        foreach ($byClassDay as $subjects) {
            usort($subjects, fn($a, $b) =>
                ($a->subject?->sessions_per_day ?? 1) <=> ($b->subject?->sessions_per_day ?? 1)
            );

            $period = 1;
            foreach ($subjects as $cs) {
                $cs->update(['default_start_period' => $period]);
                $period += $cs->subject?->sessions_per_day ?? 1;
            }
        }
    }

    public function down(): void
    {
        Schema::table('class_subjects', function (Blueprint $table) {
            $table->dropColumn('default_start_period');
        });
    }
};
