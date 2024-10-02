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
        Schema::create('sliders_previsualizations', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('title', 255);
            $table->text('description');
            $table->text('image_path');
            $table->string('color', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders_previsualizations');
    }
};
