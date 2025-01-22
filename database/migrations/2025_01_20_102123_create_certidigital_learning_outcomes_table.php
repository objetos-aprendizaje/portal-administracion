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
        Schema::create('certidigital_learning_outcomes', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('learning_result_uid')->nullable();
            $table->uuid('certidigital_achievement_uid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certidigital_learning_outcomes');
    }
};
