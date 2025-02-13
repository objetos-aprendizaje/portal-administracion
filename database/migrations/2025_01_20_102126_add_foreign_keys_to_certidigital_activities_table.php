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
        Schema::table('certidigital_activities', function (Blueprint $table) {
            $table->foreign(['certidigital_achievement_uid'])->references(['uid'])->on('certidigital_achievements')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certidigital_activities', function (Blueprint $table) {
            $table->dropForeign('certidigital_activities_certidigital_achievement_uid_foreign');
        });
    }
};
