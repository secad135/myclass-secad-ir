<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_scans', function (Blueprint $table) {
            $table->id();
            $table->string('sha256', 64)->index();
            $table->string('original_name');
            $table->string('extension', 16);
            $table->string('mime', 128);
            $table->unsignedBigInteger('size');
            $table->string('context', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('verdict', 16)->index();
            $table->json('reasons')->nullable();
            $table->json('engines')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['sha256', 'verdict']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_scans');
    }
};
