<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLicenseTypeInEducationalResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            // Eliminar la columna license_type existente
            $table->dropColumn('license_type');
        });

        Schema::table('educational_resources', function (Blueprint $table) {
            // Añadir la nueva columna license_type_uid como varchar de 36 caracteres
            $table->uuid('license_type_uid', 36)->nullable();
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
            // Eliminar la nueva columna license_type_uid
            $table->dropColumn('license_type_uid');
        });

        Schema::table('educational_resources', function (Blueprint $table) {
            // Volver a crear la columna license_type con el tipo de dato original
            $table->string('license_type', 200)->nullable();
        });
    }
}
