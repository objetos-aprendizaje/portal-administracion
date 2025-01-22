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
        Schema::create('certidigital_activities', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('certidigital_achievement_uid')->nullable();
            $table->timestamps();
            $table->string('certidigital_credential_uid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certidigital_activities');
    }
};
