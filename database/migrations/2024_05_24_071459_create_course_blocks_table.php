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
        Schema::create('course_blocks', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('course_uid', 36)->index('qvkei_course_blocks_course_uid_foreign');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order');
            $table->enum('type', ['THEORETIC', 'PRACTICAL', 'EVALUATION']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_blocks');
    }
};
