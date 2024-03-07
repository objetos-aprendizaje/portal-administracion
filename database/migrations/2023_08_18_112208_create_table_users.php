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
            $table->string('uid', 36)->primary();
            $table->string('user_rol_uid', 36);
            $table->string('first_name', 100);
            $table->string('last_name', 255);
            $table->string('nif', 12);
            $table->text('photo_path')->nullable();
            $table->string('email', 150);
            $table->text('curriculum')->nullable();
            $table->boolean('logged_x509')->default(false);
            $table->timestamps();
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
