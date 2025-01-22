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
        Schema::table('certidigital_learning_outcomes', function (Blueprint $table) {
            $table->foreign(['certidigital_achievement_uid'], 'certidigital_learning_outcomes_certidigital_achievement_uid_for')->references(['uid'])->on('certidigital_achievements')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certidigital_learning_outcomes', function (Blueprint $table) {
            $table->dropForeign('certidigital_learning_outcomes_certidigital_achievement_uid_for');
        });
    }
};
