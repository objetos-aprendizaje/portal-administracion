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
        Schema::create('destinations_general_notifications_users', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('user_uid');
            $table->uuid('general_notification_uid');
            $table->foreign('user_uid', 'user_uid_fk')->references('uid')->on('users');
            $table->foreign('general_notification_uid', 'general_notification_uid_fk')->references('uid')->on('general_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('destinations_general_notifications_users');
    }
};
