<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_general_notifications', function (Blueprint $table) {
            $table->foreign(['general_notification_uid'], 'fk_general_notification_uid')->references(['uid'])->on('general_notifications')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_general_notifications', function (Blueprint $table) {
            $table->dropForeign('fk_general_notification_uid');
            $table->dropForeign('user_general_notifications_user_uid_foreign');
        });
    }
};
