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
        Schema::create('footer_pages', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('name');
            $table->text('content');
            $table->timestamps();
            $table->string('slug')->nullable();
            $table->boolean('acceptance_required')->default(false);
            $table->decimal('version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footer_pages');
    }
};
