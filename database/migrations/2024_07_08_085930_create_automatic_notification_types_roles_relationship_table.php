<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomaticNotificationTypesRolesRelationshipTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('automatic_notification_types_roles_relationship', function (Blueprint $table) {
            $table->uuid('automatic_notification_type_uid', 36);
            $table->uuid('user_role_uid', 36);

            $table->foreign('automatic_notification_type_uid', 'ant_uid_foreign')->references('uid')->on('automatic_notification_types')->onDelete('cascade');
            $table->foreign('user_role_uid', 'user_uid_foreign')->references('uid')->on('user_roles')->onDelete('cascade');
            $table->primary(['automatic_notification_type_uid', 'user_role_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('automatic_notification_types_roles_relationship');
    }
}
