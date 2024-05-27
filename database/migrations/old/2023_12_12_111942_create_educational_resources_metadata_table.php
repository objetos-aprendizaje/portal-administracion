<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalResourcesMetadataTable extends Migration
{
    public function up()
    {
        Schema::create('educational_resources_metadata', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('educational_resources_uid', 36);
            $table->text('metadata_key');
            $table->text('metadata_value');
            $table->timestamps();

            $table->foreign('educational_resources_uid', 'fk_educ_resources_meta')
                  ->references('uid')->on('educational_resources')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('educational_resources_metadata');
    }
}
