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
        Schema::create('learning_results', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('name');
            $table->string('description', 1000)->nullable();
            $table->uuid('competence_uid')->index('qvkei_learning_results_competence_uid_foreign');
            $table->string('origin_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_results');
    }
};
