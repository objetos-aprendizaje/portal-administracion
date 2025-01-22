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
        Schema::create('destinations_general_notifications_roles', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('general_notification_uid')->index('gen_notif_uid_fk');
            $table->uuid('rol_uid')->index('rol_uid_fk');

            $table->unique(['general_notification_uid', 'rol_uid'], 'unique_general_notification_uid_rol_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations_general_notifications_roles');
    }
};
