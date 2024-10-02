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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('first_name', 100);
            $table->string('last_name')->nullable();
            $table->string('nif')->nullable();
            $table->text('photo_path')->nullable();
            $table->string('email', 150);
            $table->string('password')->nullable();
            $table->text('curriculum')->nullable();
            $table->boolean('logged_x509')->default(false);
            $table->timestamps();
            $table->string('department')->nullable();
            $table->boolean('general_notifications_allowed')->default(false);
            $table->boolean('email_notifications_allowed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
