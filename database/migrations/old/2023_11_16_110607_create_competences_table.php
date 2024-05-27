<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompetencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('competences', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->tinyInteger('is_multi_select');
            $table->string('parent_competence_uid', 36)->nullable()->index();

            $table->timestamps();
        });

        Schema::table('competences', function (Blueprint $table) {

            $table->foreign('parent_competence_uid')
                ->references('uid')->on('competences')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('competences');

        Schema::table('competences', function (Blueprint $table) {
            $table->dropForeign(['parent_competence_uid']);
        });
    }
}
