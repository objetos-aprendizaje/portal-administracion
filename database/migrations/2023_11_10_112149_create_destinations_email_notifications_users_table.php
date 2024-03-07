<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDestinationsEmailNotificationsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('destinations_email_notifications_users', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('email_notification_uid', 36);


            $table->foreign('user_uid', 'user_fk')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('email_notification_uid', 'email_notification_fk')->references('uid')->on('email_notifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('destinations_email_notifications_users');
    }
}
