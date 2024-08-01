<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationFlagsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('general_notifications_allowed')->default(1)->change();
            $table->boolean('email_notifications_allowed')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('general_notifications_allowed')->default(0)->change();
            $table->boolean('email_notifications_allowed')->default(0)->change();
        });
    }
}
