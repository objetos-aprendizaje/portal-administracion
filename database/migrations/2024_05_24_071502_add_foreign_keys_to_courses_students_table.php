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
        Schema::table('courses_students', function (Blueprint $table) {
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_students', function (Blueprint $table) {
            $table->dropForeign('qvkei_courses_students_course_uid_foreign');
            $table->dropForeign('qvkei_courses_students_user_uid_foreign');
        });
    }
};
