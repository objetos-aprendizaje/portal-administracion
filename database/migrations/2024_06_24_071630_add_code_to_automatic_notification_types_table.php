<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodeToAutomaticNotificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('automatic_notification_types', function (Blueprint $table) {
            // Añade la columna 'code' después de la columna 'name'
            $table->string('code', 100)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('automatic_notification_types', function (Blueprint $table) {
            // Elimina la columna 'code' si es necesario revertir la migración
            $table->dropColumn('code');
        });
    }
}
