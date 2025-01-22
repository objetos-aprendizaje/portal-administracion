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
        Schema::table('competences', function (Blueprint $table) {
            $table->foreign(['competence_framework_uid'])->references(['uid'])->on('competence_frameworks')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['parent_competence_uid'])->references(['uid'])->on('competences')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competences', function (Blueprint $table) {
            $table->dropForeign('competences_competence_framework_uid_foreign');
            $table->dropForeign('competences_parent_competence_uid_foreign');
        });
    }
};
