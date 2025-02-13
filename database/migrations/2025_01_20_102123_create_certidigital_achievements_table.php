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
        Schema::create('certidigital_achievements', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('certidigital_credential_uid')->nullable();
            $table->uuid('certidigital_achievement_uid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certidigital_achievements');
    }
};
