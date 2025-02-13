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
        Schema::create('certidigital_assesments', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('certidigital_achievement_uid')->nullable();
            $table->timestamps();
            $table->uuid('course_block_uid')->nullable();
            $table->uuid('learning_result_uid')->nullable();
            $table->uuid('course_uid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certidigital_assesments');
    }
};
