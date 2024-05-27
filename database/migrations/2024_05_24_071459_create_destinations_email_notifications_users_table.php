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
        Schema::create('destinations_email_notifications_users', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36)->index('user_fk');
            $table->string('email_notification_uid', 36)->index('email_notification_fk');

            $table->unique(['user_uid', 'email_notification_uid'], 'unique_user_uid_email_notification_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations_email_notifications_users');
    }
};
