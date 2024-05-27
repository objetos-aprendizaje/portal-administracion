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
        Schema::create('destinations_email_notifications_roles', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('email_notification_uid', 36)->index('dest_email_notif_idx');
            $table->string('rol_uid', 36)->index('dest_role_idx');
            $table->timestamps();

            $table->unique(['email_notification_uid', 'rol_uid'], 'unique_email_notification_uid_rol_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations_email_notifications_roles');
    }
};
