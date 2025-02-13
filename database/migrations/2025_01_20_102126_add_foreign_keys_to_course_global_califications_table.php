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
        Schema::table('course_global_califications', function (Blueprint $table) {
            $table->foreign(['course_uid'])->references(['uid'])->on('courses')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_global_califications', function (Blueprint $table) {
            $table->dropForeign('course_global_califications_course_uid_foreign');
            $table->dropForeign('course_global_califications_user_uid_foreign');
        });
    }
};
