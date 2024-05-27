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
            $table->string('uid', 36)->primary();
            $table->string('title', 100);
            $table->text('description');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->timestamps();
            $table->enum('type', ['ROLES', 'USERS', 'ALL_USERS'])->nullable();
            $table->string('notification_type_uid')->index('notification_type_uid');
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
