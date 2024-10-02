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
        Schema::create('courses_visits', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();

            $table->uuid('course_uid', 36);
            $table->foreign('course_uid', 'cour_uid_cour_vis_user_uid_fk')
                ->references('uid')->on('courses')
                ->onDelete('cascade');


            $table->uuid("user_uid", 36)->nullable();
            $table->foreign('user_uid', 'usr_cour_vis_user_uid_fk')
                ->references('uid')->on('users')
                ->onDelete('cascade');

            $table->timestamp('access_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_visits');
    }
};
