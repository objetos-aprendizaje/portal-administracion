<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $managementRol = DB::table('user_roles')->where('code', 'MANAGEMENT')->first();
        $teacherRol = DB::table('user_roles')->where('code', 'TEACHER')->first();
        $studentRol = DB::table('user_roles')->where('code', 'STUDENT')->first();

        $educationalProgramEnrollCom = DB::table('automatic_notification_types')->where('code', 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS')->first();

        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $educationalProgramEnrollCom->uid,
            'user_role_uid' => $studentRol->uid,
        ]);

        $courseEnrollCom = DB::table('automatic_notification_types')->where('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $courseEnrollCom->uid,
            'user_role_uid' => $studentRol->uid,
        ]);

        $changeStatusCourseCom = DB::table('automatic_notification_types')->where('code', 'CHANGE_STATUS_COURSE')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $changeStatusCourseCom->uid,
            'user_role_uid' => $teacherRol->uid,
        ]);

        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $changeStatusCourseCom->uid,
            'user_role_uid' => $managementRol->uid,
        ]);

        $newCoursesCom = DB::table('automatic_notification_types')->where('code', 'NEW_COURSES_NOTIFICATIONS')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $newCoursesCom->uid,
            'user_role_uid' => $studentRol->uid,
        ]);

        $newResourcesCom = DB::table('automatic_notification_types')->where('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $newResourcesCom->uid,
            'user_role_uid' => $studentRol->uid,
        ]);

        $newEducationalProgramCom = DB::table('automatic_notification_types')->where('code', 'NEW_EDUCATIONAL_PROGRAMS')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $newEducationalProgramCom->uid,
            'user_role_uid' => $studentRol->uid,
        ]);

        $changeStatusEducationalProgramCom = DB::table('automatic_notification_types')->where('code', 'CHANGE_STATUS_EDUCATIONAL_PROGRAM')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $changeStatusEducationalProgramCom->uid,
            'user_role_uid' => $teacherRol->uid,
        ]);
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $changeStatusEducationalProgramCom->uid,
            'user_role_uid' => $managementRol->uid,
        ]);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
