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
        Schema::table('general_notifications', function (Blueprint $table) {
            $table->foreign(['notification_type_uid'], 'qvkei_general_notifications_ibfk_1')->references(['uid'])->on('notifications_types')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_notifications', function (Blueprint $table) {
            $table->dropForeign('qvkei_general_notifications_ibfk_1');
        });
    }
};
