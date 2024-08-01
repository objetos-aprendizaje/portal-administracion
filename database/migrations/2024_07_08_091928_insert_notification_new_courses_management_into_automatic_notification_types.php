<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insertar en automatic_notification_types
        $uid = Str::uuid()->toString();
        DB::table('automatic_notification_types')->insert([
            'uid' => $uid,
            'name' => 'Comunicaciones para gestores sobre nuevos cursos',
            'description' => 'Notificaciones relativas a la creación de nuevos cursos en la plataforma',
            'code' => 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Extraemos los roles de TEACHER
        $rolManagement = DB::table('user_roles')->where('code', 'MANAGEMENT')->first();

        // Insertar en automatic_notification_types_roles_relationship
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $uid,
            'user_role_uid' => $rolManagement->uid,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
