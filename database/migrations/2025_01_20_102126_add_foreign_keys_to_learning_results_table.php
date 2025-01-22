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
        Schema::table('learning_results', function (Blueprint $table) {
            $table->foreign(['competence_uid'])->references(['uid'])->on('competences')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_results', function (Blueprint $table) {
            $table->dropForeign('learning_results_competence_uid_foreign');
        });
    }
};
