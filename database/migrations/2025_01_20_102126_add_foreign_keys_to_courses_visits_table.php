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
        Schema::table('courses_visits', function (Blueprint $table) {
            $table->foreign(['course_uid'], 'cour_uid_cour_vis_user_uid_fk')->references(['uid'])->on('courses')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses_visits', function (Blueprint $table) {
            $table->dropForeign('cour_uid_cour_vis_user_uid_fk');
            $table->dropForeign('courses_visits_user_uid_foreign');
        });
    }
};
