<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_school_manager')->default(false)->after('is_student');
            // کلید خارجی به جدول مدارس (اگر مدرسه حذف شد، این فیلد خالی شود)
            $table->foreignId('school_id')->nullable()->after('is_school_manager')->constrained('schools')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn(['is_school_manager', 'school_id']);
        });
    }
};
