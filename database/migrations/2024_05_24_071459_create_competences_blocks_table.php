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
            $table->string('competence_uid', 36)->index('qvkei_competences_blocks_competence_uid_foreign');
            $table->string('course_block_uid', 36)->index('qvkei_competences_blocks_course_block_uid_foreign');
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
