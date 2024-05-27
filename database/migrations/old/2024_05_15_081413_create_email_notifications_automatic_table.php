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
        Schema::create('email_notifications_automatic', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('subject', 255);
            $table->string('template', 255);
            $table->string('user_uid', 36);
            $table->text('parameters');
            $table->boolean('sent')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notifications_automatic');
    }
};
