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
        Schema::table('destinations_general_notifications_users', function (Blueprint $table) {
            $table->foreign(['general_notification_uid'], 'general_notification_uid_fk')->references(['uid'])->on('general_notifications')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_uid'], 'user_uid_fk')->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations_general_notifications_users', function (Blueprint $table) {
            $table->dropForeign('general_notification_uid_fk');
            $table->dropForeign('user_uid_fk');
        });
    }
};
