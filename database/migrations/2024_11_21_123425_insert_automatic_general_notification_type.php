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
        $uid = generate_uuid();
        DB::table('automatic_notification_types')->insert([
            'uid' => $uid,
            'code' => 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS',
            'name' => 'Nuevos recursos educativos para revisar',
            'description' => 'Notificación que se envía a los gestores cuando hay un nuevo recurso educativo pendiente de revisión',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $managementRole = DB::table('user_roles')->where('code', 'MANAGEMENT')->first();
        DB::table('automatic_notification_types_roles_relationship')->insert([
            'automatic_notification_type_uid' => $uid,
            'user_role_uid' => $managementRole->uid,
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
