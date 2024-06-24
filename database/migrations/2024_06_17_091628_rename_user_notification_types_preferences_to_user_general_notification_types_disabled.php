<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameUserNotificationTypesPreferencesToUserGeneralNotificationTypesDisabled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('user_notification_types_preferences', 'user_general_notification_types_disabled');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('user_general_notification_types_disabled', 'user_notification_types_preferences');
    }
}
