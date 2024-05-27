<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyForeignKeysOnDestinationsGeneralNotificationsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('destinations_general_notifications_users', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign('user_uid_fk');
            $table->dropForeign('general_notification_uid_fk');

            // Add foreign keys with cascade on update and delete
            $table->foreign('user_uid', 'user_uid_fk')
                ->references('uid')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('general_notification_uid', 'general_notification_uid_fk')
                ->references('uid')->on('general_notifications')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('destinations_general_notifications_users', function (Blueprint $table) {
            // Drop the modified foreign keys
            $table->dropForeign('user_uid_fk');
            $table->dropForeign('general_notification_uid_fk');

            // Add the original foreign keys without cascade
            $table->foreign('user_uid', 'user_uid_fk')->references('uid')->on('users');
            $table->foreign('general_notification_uid', 'general_notification_uid_fk')->references('uid')->on('general_notifications');
        });
    }
}
