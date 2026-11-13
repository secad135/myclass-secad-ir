<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->foreignId('correct_option_id')
                ->nullable()
                ->after('selected_option_id')
                ->constrained('question_options')
                ->nullOnDelete();
        });

        // پر کردن snapshot برای تلاش‌های قبلی
        DB::table('exam_attempt_answers')->orderBy('id')->chunkById(200, function ($answers) {
            foreach ($answers as $answer) {
                $correctOptionId = DB::table('question_options')
                    ->where('question_id', $answer->question_id)
                    ->where('is_correct', true)
                    ->value('id');

                if ($correctOptionId) {
                    DB::table('exam_attempt_answers')
                        ->where('id', $answer->id)
                        ->update(['correct_option_id' => $correctOptionId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('correct_option_id');
        });
    }
};
