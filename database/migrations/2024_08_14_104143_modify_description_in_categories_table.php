<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Eliminar la columna 'description'
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Re-crear la columna 'description' con el nuevo tipo de datos varchar(256)
        Schema::table('categories', function (Blueprint $table) {
            $table->string('description', 256)->nullable(); // Usa nullable() si deseas permitir valores nulos
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         // Eliminar la columna 'description' con el tipo de datos varchar(256)
         Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Re-crear la columna 'description' con el tipo de datos original text
        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable(); // Usa nullable() si deseas permitir valores nulos
        });
    }
};
