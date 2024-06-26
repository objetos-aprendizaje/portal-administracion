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
        Schema::create('courses_small_carrousels_approvals', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('course_uid', 36);
            $table->foreign('course_uid')->references('uid')->on('courses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_small_carrousels_approvals');
    }
};
