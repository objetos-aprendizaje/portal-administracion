<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeneralNotificationTypeUidToGeneralNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_notifications', function (Blueprint $table) {
            $table->string('general_notification_type_uid', 36)->after('uid')->nullable();

            $table->foreign('general_notification_type_uid', 'gnt_uid_foreign')
                ->references('uid')
                ->on('general_notification_types')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_notifications', function (Blueprint $table) {
            $table->dropForeign(['general_notification_type_uid']);
            $table->dropColumn('general_notification_type_uid');
        });
    }
}
