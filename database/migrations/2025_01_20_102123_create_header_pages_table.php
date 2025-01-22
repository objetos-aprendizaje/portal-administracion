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
        Schema::create('header_pages', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('name');
            $table->text('content');
            $table->timestamps();
            $table->uuid('header_page_uid')->nullable();
            $table->integer('order');
            $table->string('slug')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('header_pages');
    }
};
