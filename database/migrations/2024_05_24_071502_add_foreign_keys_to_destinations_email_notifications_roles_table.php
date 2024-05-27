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
        Schema::table('destinations_email_notifications_roles', function (Blueprint $table) {
            $table->foreign(['email_notification_uid'], 'dest_email_notif_fk')->references(['uid'])->on('email_notifications')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['rol_uid'], 'dest_role_fk')->references(['uid'])->on('user_roles')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations_email_notifications_roles', function (Blueprint $table) {
            $table->dropForeign('dest_email_notif_fk');
            $table->dropForeign('dest_role_fk');
        });
    }
};
