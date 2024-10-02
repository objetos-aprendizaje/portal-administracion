<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserEmailNotificationTypesDisabledTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_email_notification_types_disabled', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('user_uid', 36);
            $table->uuid('notification_type_uid', 36);

            $table->foreign('user_uid', 'user_email_notif_user_uid_fk')->references('uid')->on('users')->onDelete('cascade');
            $table->foreign('notification_type_uid', 'user_email_notif_type_uid_fk')->references('uid')->on('notifications_types')->onDelete('cascade');
            $table->unique(['user_uid', 'notification_type_uid'], 'user_notif_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_email_notification_types_disabled');
    }
}
