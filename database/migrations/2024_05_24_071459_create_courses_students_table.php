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
        Schema::create('courses_students', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('course_uid', 36)->index('qvkei_courses_students_course_uid_foreign');
            $table->string('user_uid', 36)->index('qvkei_courses_students_user_uid_foreign');
            $table->enum('calification_type', ['NUMERIC', 'TEXTUAL'])->nullable();
            $table->string('calification')->nullable();
            $table->timestamps();
            $table->boolean('approved')->nullable();
            $table->string('credential')->nullable();
            $table->enum('status', ['INSCRIBED', 'ENROLLED'])->default('INSCRIBED');

            $table->unique(['course_uid', 'user_uid'], 'unique_course_user_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_students');
    }
};
