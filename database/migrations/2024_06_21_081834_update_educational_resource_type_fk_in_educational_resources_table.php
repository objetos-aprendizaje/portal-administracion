<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEducationalResourceTypeFkInEducationalResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_resources', function (Blueprint $table) {

            $table->dropForeign('qvkei_educational_resources_ibfk_1');
            $table->foreign('educational_resource_type_uid')->references('uid')->on('educational_resource_types')
                ->onUpdate('cascade')
                ->onDelete('cascade')->name('qvkei_educational_resources_ibfk_1');
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
        });
    }
}
