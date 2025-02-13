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
        Schema::create('courses_assessments', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('user_uid')->index('qvkei_courses_assessments_user_uid_foreign');
            $table->uuid('course_uid')->index('qvkei_courses_assessments_course_uid_foreign');
            $table->integer('calification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_assessments');
    }
};
