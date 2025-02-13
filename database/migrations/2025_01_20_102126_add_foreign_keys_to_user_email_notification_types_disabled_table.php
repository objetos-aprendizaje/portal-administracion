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
        Schema::table('user_email_notification_types_disabled', function (Blueprint $table) {
            $table->foreign(['notification_type_uid'], 'user_email_notif_type_uid_fk')->references(['uid'])->on('notifications_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_email_notification_types_disabled', function (Blueprint $table) {
            $table->dropForeign('user_email_notif_type_uid_fk');
            $table->dropForeign('user_email_notification_types_disabled_user_uid_foreign');
        });
    }
};
