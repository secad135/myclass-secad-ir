<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->boolean('requires_code')->default(false)->after('description');
            $table->boolean('allow_file_upload')->default(true)->after('requires_code');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['requires_code', 'allow_file_upload']);
        });
    }
};
