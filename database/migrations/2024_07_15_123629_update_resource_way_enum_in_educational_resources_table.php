<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateResourceWayEnumInEducationalResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            // Eliminar la columna resource_way
            $table->dropColumn('resource_way');
        });

        Schema::table('educational_resources', function (Blueprint $table) {
            // Crear la nueva columna resource_way con los valores actualizados
            $table->enum('resource_way', ['URL', 'FILE', 'IMAGE', 'PDF', 'VIDEO', 'AUDIO'])->default('URL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            // Eliminar la columna resource_way con los valores actualizados
            $table->dropColumn('resource_way');
        });

        Schema::table('educational_resources', function (Blueprint $table) {
            // Volver a crear la columna resource_way con los valores originales
            $table->enum('resource_way', ['URL', 'FILE'])->default('URL');
        });
    }
}
