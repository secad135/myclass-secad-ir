<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE attendances
            MODIFY COLUMN status
            ENUM('present', 'absent', 'late', 'excused', 'left', 'cancelled')
            NOT NULL DEFAULT 'absent'
        ");
    }

    public function down(): void
    {
        DB::table('attendances')->where('status', 'cancelled')->update(['status' => 'absent']);
        DB::statement("
            ALTER TABLE attendances
            MODIFY COLUMN status
            ENUM('present', 'absent', 'late', 'excused', 'left')
            NOT NULL DEFAULT 'absent'
        ");
    }
};