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
        Schema::create('email_notifications', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('subject');
            $table->text('body');
            $table->enum('type', ['ROLES', 'USERS', 'ALL_USERS'])->nullable();
            $table->dateTime('send_date')->nullable();
            $table->boolean('sent')->default(false);
            $table->uuid('notification_type_uid', 36)->nullable()->index('notification_type_uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notifications');
    }
};
