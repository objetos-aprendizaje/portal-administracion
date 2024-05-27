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
        Schema::create('user_courses', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36)->index('qvkei_user_courses_user_uid_foreign');
            $table->string('course_uid', 36)->index('qvkei_user_courses_course_uid_foreign');
            $table->string('role', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_courses');
    }
};
