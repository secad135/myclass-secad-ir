<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('class_subject_id')
                ->constrained('class_subjects')
                ->onDelete('cascade');

            $table->foreignId('point_category_id')
                ->nullable()
                ->constrained('point_categories')
                ->onDelete('set null');

            $table->integer('points');                  // مقدار امتیاز این ثبت (از پیش‌فرض کپی می‌شود ولی قابل تغییر است)
            $table->text('note')->nullable();
            $table->date('entry_date');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();

            $table->index(['class_subject_id', 'entry_date']);
            $table->index(['student_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_entries');
    }
};
