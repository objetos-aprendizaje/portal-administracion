<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('destinations_general_notifications_roles', function (Blueprint $table) {
            $table->uuid('uid')->primary();  // Primary Key
            $table->uuid('general_notification_uid');  // Foreign Key de general_notifications
            $table->uuid('rol_uid');  // Foreign Key de user_roles

            // Definición de las Foreign Keys
            $table->foreign('general_notification_uid', 'gen_notif_uid_fk')->references('uid')->on('general_notifications')->onDelete('cascade');
            $table->foreign('rol_uid')->references('uid')->on('user_roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('destinations_general_notifications_roles');
    }
};
