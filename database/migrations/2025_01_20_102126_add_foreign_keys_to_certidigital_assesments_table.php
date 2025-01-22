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
        Schema::table('certidigital_assesments', function (Blueprint $table) {
            $table->foreign(['certidigital_achievement_uid'])->references(['uid'])->on('certidigital_achievements')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['course_block_uid'])->references(['uid'])->on('course_blocks')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['learning_result_uid'])->references(['uid'])->on('learning_results')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certidigital_assesments', function (Blueprint $table) {
            $table->dropForeign('certidigital_assesments_certidigital_achievement_uid_foreign');
            $table->dropForeign('certidigital_assesments_course_block_uid_foreign');
            $table->dropForeign('certidigital_assesments_course_uid_foreign');
            $table->dropForeign('certidigital_assesments_learning_result_uid_foreign');
        });
    }
};
