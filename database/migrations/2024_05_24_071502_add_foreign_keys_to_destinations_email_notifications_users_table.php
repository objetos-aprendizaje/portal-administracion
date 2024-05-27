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
        Schema::table('destinations_email_notifications_users', function (Blueprint $table) {
            $table->foreign(['email_notification_uid'], 'email_notification_fk')->references(['uid'])->on('email_notifications')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_uid'], 'user_fk')->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations_email_notifications_users', function (Blueprint $table) {
            $table->dropForeign('email_notification_fk');
            $table->dropForeign('user_fk');
        });
    }
};
