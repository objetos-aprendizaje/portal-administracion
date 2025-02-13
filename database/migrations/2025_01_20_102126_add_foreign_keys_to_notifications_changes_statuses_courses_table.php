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
        Schema::table('notifications_changes_statuses_courses', function (Blueprint $table) {
            $table->foreign(['course_uid'], 'ncsc_course_fk')->references(['uid'])->on('courses')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['course_status_uid'], 'ncsc_status_fk')->references(['uid'])->on('course_statuses')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications_changes_statuses_courses', function (Blueprint $table) {
            $table->dropForeign('ncsc_course_fk');
            $table->dropForeign('ncsc_status_fk');
            $table->dropForeign('notifications_changes_statuses_courses_user_uid_foreign');
        });
    }
};
