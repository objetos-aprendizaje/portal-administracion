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
            $table->foreign(['certidigital_credential_uid'])->references(['uid'])->on('certidigital_credentials')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['certidigital_teacher_credential_uid'])->references(['uid'])->on('certidigital_credentials')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['certification_type_uid'])->references(['uid'])->on('certification_types')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['course_origin_uid'])->references(['uid'])->on('courses')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['creator_user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['educational_program_uid'])->references(['uid'])->on('educational_programs')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['lms_system_uid'])->references(['uid'])->on('lms_systems')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['call_uid'], 'qvkei_courses_ibfk_1')->references(['uid'])->on('calls')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['course_status_uid'], 'qvkei_courses_ibfk_2')->references(['uid'])->on('course_statuses')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['course_type_uid'], 'qvkei_courses_ibfk_3')->references(['uid'])->on('course_types')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign('courses_center_uid_foreign');
            $table->dropForeign('courses_certidigital_credential_uid_foreign');
            $table->dropForeign('courses_certidigital_teacher_credential_uid_foreign');
            $table->dropForeign('courses_certification_type_uid_foreign');
            $table->dropForeign('courses_course_origin_uid_foreign');
            $table->dropForeign('courses_creator_user_uid_foreign');
            $table->dropForeign('courses_educational_program_uid_foreign');
            $table->dropForeign('courses_lms_system_uid_foreign');
            $table->dropForeign('qvkei_courses_ibfk_1');
            $table->dropForeign('qvkei_courses_ibfk_2');
            $table->dropForeign('qvkei_courses_ibfk_3');
        });
    }
};
