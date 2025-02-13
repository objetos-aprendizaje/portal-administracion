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
            $table->uuid('uid')->primary();
            $table->uuid('competence_uid')->index('qvkei_competences_blocks_competence_uid_foreign');
            $table->uuid('course_block_uid')->index('qvkei_competences_blocks_course_block_uid_foreign');
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
