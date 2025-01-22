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
        Schema::create('educational_resources_learning_results', function (Blueprint $table) {
            $table->uuid('educational_resource_uid');
            $table->uuid('learning_result_uid');

            $table->primary(['educational_resource_uid', 'learning_result_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources_learning_results');
    }
};
