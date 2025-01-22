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
        Schema::table('course_learning_result_califications', function (Blueprint $table) {
            $table->foreign(['competence_framework_level_uid'], 'course_learning_result_califications_competence_framework_level')->references(['uid'])->on('competence_frameworks_levels')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['learning_result_uid'], 'course_learning_result_califications_learning_result_uid_foreig')->references(['uid'])->on('learning_results')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_learning_result_califications', function (Blueprint $table) {
            $table->dropForeign('course_learning_result_califications_competence_framework_level');
            $table->dropForeign('course_learning_result_califications_course_uid_foreign');
            $table->dropForeign('course_learning_result_califications_learning_result_uid_foreig');
            $table->dropForeign('course_learning_result_califications_user_uid_foreign');
        });
    }
};
