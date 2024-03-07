<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserNotificationTypesPreferencesTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_notification_types_preferences', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('user_uid', 36);
            $table->string('notification_type_uid', 36);

            $table->foreign('user_uid')
                  ->references('uid')->on('users')
                  ->onDelete('cascade');

                  $table->foreign('notification_type_uid', 'nt_uid_foreign')
                  ->references('uid')->on('notifications_types')
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
        Schema::dropIfExists('user_notification_types_preferences');
    }
};
