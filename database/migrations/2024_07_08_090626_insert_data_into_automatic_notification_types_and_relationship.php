<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsertDataIntoAutomaticNotificationTypesAndRelationship extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Insertar en automatic_notification_types
        $uid = Str::uuid()->toString(); // Generar un UUID para el nuevo registro
        DB::table('automatic_notification_types')->insert([
            'uid' => $uid,
            'name' => 'Comunicaciones sobre cursos inscritos como docente',
            'description' => 'Notificaciones relativas al cambio de estado de cursos creados por tí o en los que figuras como docente',
            'code' => 'COURSE_ENROLLMENT_TEACHER_COMMUNICATIONS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Extraemos los roles de TEACHER
        $rolTeacher = DB::table('user_roles')->where('code', 'TEACHER')->first();

        // Insertar en automatic_notification_types_roles_relationship
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $uid,
            'user_role_uid' => $rolTeacher->uid,
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Opcional: Código para revertir los cambios si es necesario.
    }
}
