<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToAutomaticNotificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('automatic_notification_types', function (Blueprint $table) {
            // Asegúrate de que el tipo de columna sea adecuado para tu uso
            $table->text('description')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('automatic_notification_types', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}
