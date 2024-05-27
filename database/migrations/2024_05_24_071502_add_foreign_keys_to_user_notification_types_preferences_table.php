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
        Schema::table('user_notification_types_preferences', function (Blueprint $table) {
            $table->foreign(['notification_type_uid'], 'nt_uid_foreign')->references(['uid'])->on('notifications_types')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_types_preferences', function (Blueprint $table) {
            $table->dropForeign('nt_uid_foreign');
            $table->dropForeign('qvkei_user_notification_types_preferences_user_uid_foreign');
        });
    }
};
