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
            $table->uuid('uid')->primary();
            $table->string('subject');
            $table->string('body', 1000)->nullable();
            $table->enum('type', ['ROLES', 'USERS', 'ALL_USERS'])->nullable();
            $table->timestamp('send_date')->nullable();
            $table->uuid('notification_type_uid')->nullable()->index('notification_type_uid');
            $table->timestamps();
            $table->enum('status', ['PENDING', 'SENT', 'FAILED'])->default('PENDING');
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
