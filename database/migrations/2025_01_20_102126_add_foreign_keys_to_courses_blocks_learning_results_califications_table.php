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
        Schema::table('courses_blocks_learning_results_califications', function (Blueprint $table) {
            $table->foreign(['competence_framework_level_uid'], 'courses_blocks_learning_results_califications_competence_framew')->references(['uid'])->on('competence_frameworks_levels')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['course_block_uid'], 'courses_blocks_learning_results_califications_course_block_uid_')->references(['uid'])->on('course_blocks')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['learning_result_uid'], 'courses_blocks_learning_results_califications_learning_result_u')->references(['uid'])->on('learning_results')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_blocks_learning_results_califications', function (Blueprint $table) {
            $table->dropForeign('courses_blocks_learning_results_califications_competence_framew');
            $table->dropForeign('courses_blocks_learning_results_califications_course_block_uid_');
            $table->dropForeign('courses_blocks_learning_results_califications_learning_result_u');
            $table->dropForeign('courses_blocks_learning_results_califications_user_uid_foreign');
        });
    }
};
