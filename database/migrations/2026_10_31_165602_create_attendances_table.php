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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_day_id')
                ->constrained('attendance_days')
                ->onDelete('cascade');

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('session_number'); // 1..4
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'left'])
                ->default('absent');
            $table->text('note')->nullable();
            $table->boolean('is_customized')->default(false);

            $table->timestamps();

            $table->unique(
                ['attendance_day_id', 'student_id', 'session_number'],
                'attendance_unique_cell'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
