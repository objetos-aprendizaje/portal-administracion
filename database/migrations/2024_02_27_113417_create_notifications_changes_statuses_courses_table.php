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
        Schema::create('notifications_changes_statuses_courses', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('user_uid');
            $table->uuid('course_uid');
            $table->uuid('course_status_uid');
            $table->timestamp('date')->useCurrent();

            $table->foreign('user_uid', 'ncsc_user_fk')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('course_uid', 'ncsc_course_fk')->references('uid')->on('courses')->onDelete('cascade');
            $table->foreign('course_status_uid', 'ncsc_status_fk')->references('uid')->on('course_statuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_changes_statuses_courses');
    }
};
