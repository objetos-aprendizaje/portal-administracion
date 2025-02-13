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
        Schema::create('course_global_califications', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('course_uid');
            $table->uuid('user_uid');
            $table->text('calification_info');
            $table->timestamps();

            $table->unique(['course_uid', 'user_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_global_califications');
    }
};
