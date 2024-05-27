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
        Schema::table('user_courses', function (Blueprint $table) {
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_courses', function (Blueprint $table) {
            $table->dropForeign('qvkei_user_courses_course_uid_foreign');
            $table->dropForeign('qvkei_user_courses_user_uid_foreign');
        });
    }
};
