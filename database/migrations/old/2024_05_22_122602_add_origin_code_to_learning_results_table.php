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
            $table->string('origin_code', 255)->nullable()->after('competence_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_results', function (Blueprint $table) {
            $table->dropColumn('origin_code');
        });
    }
};
