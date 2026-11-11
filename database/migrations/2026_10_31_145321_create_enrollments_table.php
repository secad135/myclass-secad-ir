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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('school_class_id')
                ->constrained('school_classes')
                ->onDelete('cascade');

            $table->enum('status', ['active', 'transferred', 'left'])
                ->default('active');

            $table->date('enrolled_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // جلوگیری از ثبت‌نام تکراری یک دانش‌آموز در یک کلاس
            $table->unique(['student_id', 'school_class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
