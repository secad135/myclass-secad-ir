<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('class_subjects', 'teacher_id')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                $table->foreignId('teacher_id')
                    ->nullable()
                    ->after('subject_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_subjects', 'teacher_id')) {
            Schema::table('class_subjects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('teacher_id');
            });
        }
    }
};
