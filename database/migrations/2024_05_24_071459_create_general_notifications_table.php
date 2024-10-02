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
        Schema::create('general_notifications', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('title', 100);
            $table->text('description');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->timestamps();
            $table->enum('type', ['ROLES', 'USERS', 'ALL_USERS'])->nullable();
            $table->uuid('notification_type_uid', 36)->index('qvkei_notification_type_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_notifications');
    }
};
