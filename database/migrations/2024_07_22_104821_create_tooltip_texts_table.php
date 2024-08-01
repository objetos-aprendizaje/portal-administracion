<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTooltipTextsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tooltip_texts', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('input_id', 100);
            $table->text('description');
            $table->timestamps(); // This will create the created_at and updated_at fields
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tooltip_texts');
    }
}
