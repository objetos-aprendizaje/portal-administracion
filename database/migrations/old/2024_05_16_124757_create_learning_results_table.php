<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_results', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('name', 255)->nullable(false);
            $table->text('description')->nullable();
            $table->string('competence_uid', 36);

            $table->foreign('competence_uid')
                ->references('uid')
                ->on('competences')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_results');
    }
};
