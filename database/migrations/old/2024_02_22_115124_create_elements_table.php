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
            $table->string('subblock_uid', 36);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('subblock_uid')->references('uid')->on('course_subblocks')->onDelete('cascade')->onUpdate('cascade');
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
