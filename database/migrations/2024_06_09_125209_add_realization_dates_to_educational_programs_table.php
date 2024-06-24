<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRealizationDatesToEducationalProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_programs', function (Blueprint $table) {
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
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dropColumn('realization_start_date');
            $table->dropColumn('realization_finish_date');
        });
    }
}
