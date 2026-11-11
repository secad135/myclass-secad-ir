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
        Schema::create('point_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // مثلاً "تکلیف انجام شده"
            $table->integer('default_points');                   // مثبت یا منفی
            $table->text('description')->nullable();             // توضیح
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_categories');
    }
};
