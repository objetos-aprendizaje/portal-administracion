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
        Schema::table('competences_blocks', function (Blueprint $table) {
            $table->foreign(['competence_uid'])->references(['uid'])->on('competences')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['course_block_uid'])->references(['uid'])->on('course_blocks')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competences_blocks', function (Blueprint $table) {
            $table->dropForeign('competences_blocks_competence_uid_foreign');
            $table->dropForeign('competences_blocks_course_block_uid_foreign');
        });
    }
};
