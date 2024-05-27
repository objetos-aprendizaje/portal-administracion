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
            $table->char('uid', 36)->primary();
            $table->char('user_uid', 36)->index('user_uid_fk');
            $table->char('general_notification_uid', 36)->index('general_notification_uid_fk');

            $table->unique(['user_uid', 'general_notification_uid'], 'unique_user_uid_general_notification_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations_general_notifications_users');
    }
};
