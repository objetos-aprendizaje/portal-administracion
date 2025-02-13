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
        Schema::table('automatic_notification_types_roles_relationship', function (Blueprint $table) {
            $table->foreign(['automatic_notification_type_uid'], 'ant_uid_foreign')->references(['uid'])->on('automatic_notification_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_role_uid'], 'user_uid_foreign')->references(['uid'])->on('user_roles')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automatic_notification_types_roles_relationship', function (Blueprint $table) {
            $table->dropForeign('ant_uid_foreign');
            $table->dropForeign('user_uid_foreign');
        });
    }
};
