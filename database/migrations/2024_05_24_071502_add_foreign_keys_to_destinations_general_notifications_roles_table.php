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
        Schema::table('destinations_general_notifications_roles', function (Blueprint $table) {
            $table->foreign(['general_notification_uid'], 'gen_notif_uid_fk')->references(['uid'])->on('general_notifications')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['rol_uid'], 'rol_uid_fk')->references(['uid'])->on('user_roles')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinations_general_notifications_roles', function (Blueprint $table) {
            $table->dropForeign('gen_notif_uid_fk');
            $table->dropForeign('rol_uid_fk');
        });
    }
};
