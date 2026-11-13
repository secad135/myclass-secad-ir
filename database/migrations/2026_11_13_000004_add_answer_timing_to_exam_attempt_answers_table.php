<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->timestamp('first_answered_at')->nullable()->after('is_correct');
            $table->timestamp('last_answered_at')->nullable()->after('first_answered_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropColumn(['first_answered_at', 'last_answered_at']);
        });
    }
};
