<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_days', function (Blueprint $table) {
            $table->unsignedTinyInteger('start_period')->default(1)->after('date');
        });

        // پر کردن داده‌های موجود از class_subjects
        DB::statement('
            UPDATE attendance_days ad
            INNER JOIN class_subjects cs ON cs.id = ad.class_subject_id
            SET ad.start_period = cs.default_start_period
        ');
    }

    public function down(): void
    {
        Schema::table('attendance_days', function (Blueprint $table) {
            $table->dropColumn('start_period');
        });
    }
};
