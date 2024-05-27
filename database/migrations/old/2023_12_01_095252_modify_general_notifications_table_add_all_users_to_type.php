<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyGeneralNotificationsTableAddAllUsersToType extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        // Modificar la columna 'type' para incluir 'ALL_USERS'
        DB::statement("ALTER TABLE " . env('DB_PREFIX') . "general_notifications MODIFY COLUMN type ENUM('ROLES', 'USERS', 'ALL_USERS')");
    }

    /**
     * Revertir las migraciones.
     *
     * @return void
     */
    public function down()
    {
        // Revertir a los valores originales en caso de rollback
        DB::statement("ALTER TABLE " . env('DB_PREFIX') . "general_notifications MODIFY COLUMN type ENUM('ROLES', 'USERS')");
    }
}
