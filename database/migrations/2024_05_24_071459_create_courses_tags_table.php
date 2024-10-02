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
        Schema::create('courses_tags', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('course_uid', 36)->index('course_uid');
            $table->string('tag', 36);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_tags');
    }
};
