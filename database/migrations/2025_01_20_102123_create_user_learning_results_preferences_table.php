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
        Schema::create('user_learning_results_preferences', function (Blueprint $table) {
            $table->uuid('learning_result_uid');
            $table->uuid('user_uid');
            $table->timestamps();

            $table->primary(['learning_result_uid', 'user_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_learning_results_preferences');
    }
};
