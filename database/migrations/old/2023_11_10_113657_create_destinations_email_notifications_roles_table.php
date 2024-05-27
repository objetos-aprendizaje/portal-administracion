<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDestinationsEmailNotificationsRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('destinations_email_notifications_roles', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('email_notification_uid', 36);
            $table->string('rol_uid', 36);
            $table->timestamps();

            // Nombres personalizados para las claves foráneas
            $table->foreign('email_notification_uid', 'dest_email_notif_fk')->references('uid')->on('email_notifications')->onDelete('cascade');
            $table->foreign('rol_uid', 'dest_role_fk')->references('uid')->on('user_roles')->onDelete('cascade');

            // Nombres personalizados para los índices para evitar errores de longitud
            $table->index('email_notification_uid', 'dest_email_notif_idx');
            $table->index('rol_uid', 'dest_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('destinations_email_notifications_roles');
    }
}
