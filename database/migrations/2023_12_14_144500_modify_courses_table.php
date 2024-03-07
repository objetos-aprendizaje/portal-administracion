<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            // Eliminar campos existentes
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');

            // Añadir nuevos campos
            $table->dateTime('inscription_start_date')->nullable();
            $table->dateTime('inscription_finish_date')->nullable();
            $table->dateTime('realization_start_date')->nullable();
            $table->dateTime('realization_finish_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            // Revertir la eliminación de campos
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // Eliminar los nuevos campos
            $table->dropColumn('inscription_start_date');
            $table->dropColumn('inscription_finish_date');
            $table->dropColumn('realization_start_date');
            $table->dropColumn('realization_finish_date');
        });
    }
}

