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
        Schema::table('general_notifications_automatic_users', function (Blueprint $table) {
            $table->foreign(['general_notifications_automatic_uid'], 'gnau_uid_foreign')->references(['uid'])->on('general_notifications_automatic')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_notifications_automatic_users', function (Blueprint $table) {
            $table->dropForeign('gnau_uid_foreign');
        });
    }
};
