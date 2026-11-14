<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('component');                 // exams | exercises | attendance | manual
            $table->decimal('weight', 5, 2)->default(0); // درصد (۰ تا ۱۰۰)
            $table->timestamps();

            $table->unique(['teacher_id', 'component']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_weights');
    }
};
