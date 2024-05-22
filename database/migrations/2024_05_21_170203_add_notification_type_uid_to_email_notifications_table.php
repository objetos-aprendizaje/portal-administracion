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
        Schema::table('email_notifications', function (Blueprint $table) {
            $table->string('notification_type_uid')->nullable()->after('sent');
        });
        Schema::dropIfExists('general_notification_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_notifications', function (Blueprint $table) {
            $table->dropColumn('notification_type_uid');
        });
    }
};
