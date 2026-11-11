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
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_subject_id')
                ->constrained('class_subjects')
                ->onDelete('cascade');

            $table->date('date');
            $table->boolean('is_started')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->unique(['class_subject_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
