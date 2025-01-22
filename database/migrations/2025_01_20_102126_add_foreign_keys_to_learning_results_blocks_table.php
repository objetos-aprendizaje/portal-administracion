<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('learning_results_blocks', function (Blueprint $table) {
            $table->foreign(['course_block_uid'])->references(['uid'])->on('course_blocks')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['learning_result_uid'])->references(['uid'])->on('learning_results')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_results_blocks', function (Blueprint $table) {
            $table->dropForeign('learning_results_blocks_course_block_uid_foreign');
            $table->dropForeign('learning_results_blocks_learning_result_uid_foreign');
        });
    }
};
