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
            $table->uuid('user_uid')->index('ncsc_user_fk');
            $table->uuid('course_uid')->index('ncsc_course_fk');
            $table->uuid('course_status_uid')->index('ncsc_status_fk');
            $table->timestamp('date')->useCurrent();
            $table->boolean('is_read')->default(false);
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
