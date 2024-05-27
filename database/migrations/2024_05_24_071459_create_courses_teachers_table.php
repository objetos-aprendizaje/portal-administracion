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
        Schema::create('courses_teachers', function (Blueprint $table) {
            $table->char('uid', 36)->primary();
            $table->char('course_uid', 36)->index('qvkei_courses_teachers_course_uid_foreign');
            $table->char('user_uid', 36)->index('qvkei_courses_teachers_user_uid_foreign');
            $table->timestamps();
            $table->string('credential')->nullable();
            $table->enum('type', ['COORDINATOR', 'NO_COORDINATOR'])->default('NO_COORDINATOR');

            $table->unique(['course_uid', 'user_uid'], 'unique_course_user_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_teachers');
    }
};
