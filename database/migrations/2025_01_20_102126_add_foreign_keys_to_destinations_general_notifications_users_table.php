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
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['general_notification_uid'], 'general_notification_uid_fk')->references(['uid'])->on('general_notifications')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations_general_notifications_users', function (Blueprint $table) {
            $table->dropForeign('destinations_general_notifications_users_user_uid_foreign');
            $table->dropForeign('general_notification_uid_fk');
        });
    }
};
