<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningResultsBlocksTable extends Migration
{
    public function up()
    {
        Schema::create('learning_results_blocks', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('learning_result_uid', 36);
            $table->string('course_block_uid', 36);

            $table->foreign('learning_result_uid')->references('uid')->on('learning_results')->onDelete('cascade');
            $table->foreign('course_block_uid')->references('uid')->on('course_blocks')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_results_blocks');
    }
}
