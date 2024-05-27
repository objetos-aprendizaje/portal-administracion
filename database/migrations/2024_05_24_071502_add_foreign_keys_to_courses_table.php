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
        Schema::table('courses', function (Blueprint $table) {
            $table->foreign(['center_uid'])->references(['uid'])->on('centers')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['course_origin_uid'])->references(['uid'])->on('courses')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['creator_user_uid'])->references(['uid'])->on('users')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['educational_program_uid'])->references(['uid'])->on('educational_programs')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['call_uid'], 'qvkei_courses_ibfk_1')->references(['uid'])->on('calls')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['course_status_uid'], 'qvkei_courses_ibfk_2')->references(['uid'])->on('course_statuses')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['course_type_uid'], 'qvkei_courses_ibfk_3')->references(['uid'])->on('course_types')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['educational_program_type_uid'], 'qvkei_courses_ibfk_4')->references(['uid'])->on('educational_program_types')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign('qvkei_courses_center_uid_foreign');
            $table->dropForeign('qvkei_courses_course_origin_uid_foreign');
            $table->dropForeign('qvkei_courses_creator_user_uid_foreign');
            $table->dropForeign('qvkei_courses_educational_program_uid_foreign');
            $table->dropForeign('qvkei_courses_ibfk_1');
            $table->dropForeign('qvkei_courses_ibfk_2');
            $table->dropForeign('qvkei_courses_ibfk_3');
            $table->dropForeign('qvkei_courses_ibfk_4');
        });
    }
};
