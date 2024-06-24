<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsertNewEducationalResourcesNotificationIntoAutomaticNotificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Inserta el registro en la tabla
        DB::table('automatic_notification_types')->insert([
            'uid' => Str::uuid(), // Genera un UUID único para el campo uid
            'name' => 'Avisos de nuevos recursos educativos',
            'code' => 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Elimina el registro específico si es necesario revertir la migración
        DB::table('automatic_notification_types')->where('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS')->delete();
    }
}
