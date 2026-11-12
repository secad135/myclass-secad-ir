<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('exams', 'created_by')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('class_subject_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('exercises', 'created_by')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('class_subject_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exams', 'created_by')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasColumn('exercises', 'created_by')) {
            Schema::table('exercises', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
