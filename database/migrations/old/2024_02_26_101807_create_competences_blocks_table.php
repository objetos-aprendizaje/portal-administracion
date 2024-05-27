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
        Schema::create('competences_blocks', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('competence_uid', 36);
            $table->string('course_block_uid', 36);

            $table->foreign('competence_uid')
                ->references('uid')->on('competences')
                ->onDelete('cascade');

            $table->foreign('course_block_uid')
                ->references('uid')->on('course_blocks')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competences_blocks');
    }
};
