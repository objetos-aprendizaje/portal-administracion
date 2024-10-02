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
        Schema::create('course_categories', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('course_uid', 36)->index('qvkei_course_categories_course_uid_foreign');
            $table->uuid('category_uid', 36)->index('qvkei_course_categories_category_uid_foreign');
            $table->timestamps();

            $table->unique(['course_uid', 'category_uid'], 'unique_course_category_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_categories');
    }
};
