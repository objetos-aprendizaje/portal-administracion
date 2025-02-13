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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('name');
            $table->uuid('parent_category_uid')->nullable()->index('qvkei_categories_parent_category_uid_foreign');
            $table->string('color');
            $table->string('image_path');
            $table->timestamps();
            $table->string('description', 1000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
