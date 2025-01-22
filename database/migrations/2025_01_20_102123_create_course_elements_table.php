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
        Schema::create('course_elements', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('subblock_uid')->index('qvkei_course_elements_subblock_uid_foreign');
            $table->string('name');
            $table->string('description', 1000)->nullable();
            $table->integer('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_elements');
    }
};
