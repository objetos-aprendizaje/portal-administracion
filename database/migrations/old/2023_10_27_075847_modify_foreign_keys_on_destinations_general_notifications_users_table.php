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

            Schema::dropIfExists('destinations_general_notifications_roles');
            Schema::create('destinations_general_notifications_roles', function (Blueprint $table) {
                $table->char('uid', 36)->primary();
                $table->char('general_notification_uid', 36);
                $table->char('rol_uid', 36);

                $table->foreign('general_notification_uid', 'gen_notif_uid_fk')
                ->references('uid')->on('general_notifications')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('rol_uid', 'rol_uid_fk')
                ->references('uid')->on('user_roles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
