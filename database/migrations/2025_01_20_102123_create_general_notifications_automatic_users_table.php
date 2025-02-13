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
        Schema::create('general_notifications_automatic_users', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('general_notifications_automatic_uid')->index('gnau_uid_foreign');
            $table->uuid('user_uid')->index('qvkei_general_notifications_automatic_users_user_uid_foreign');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_notifications_automatic_users');
    }
};
