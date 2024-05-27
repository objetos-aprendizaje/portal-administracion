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
            $table->string('uid', 36)->primary();
            $table->string('subblock_uid', 36)->index('qvkei_course_elements_subblock_uid_foreign');
            $table->string('name');
            $table->text('description')->nullable();
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
