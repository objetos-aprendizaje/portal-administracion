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
        Schema::create('tooltip_texts', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('input_id', 100);
            $table->string('description', 1000)->nullable();
            $table->timestamps();
            $table->string('form_id', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooltip_texts');
    }
};
