<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertNewEducationalProgramsNotificationType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $uid = generate_uuid();
        DB::table('automatic_notification_types')->insert([
            'uid' => $uid,
            'name' => 'Comunicaciones para gestores sobre nuevos programas formativos',
            'description' => 'Notificaciones dirigidas a los gestores relativas a la creación de nuevos cursos en la plataforma',
            'code' => 'NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS',
        ]);

        $rolManagement = DB::table('user_roles')->where('code', 'MANAGEMENT')->first();

        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $uid,
            'user_role_uid' => $rolManagement->uid,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
