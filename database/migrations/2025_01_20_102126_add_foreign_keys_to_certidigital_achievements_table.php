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
        Schema::table('certidigital_achievements', function (Blueprint $table) {
            $table->foreign(['certidigital_achievement_uid'])->references(['uid'])->on('certidigital_achievements')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['certidigital_credential_uid'])->references(['uid'])->on('certidigital_credentials')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certidigital_achievements', function (Blueprint $table) {
            $table->dropForeign('certidigital_achievements_certidigital_achievement_uid_foreign');
            $table->dropForeign('certidigital_achievements_certidigital_credential_uid_foreign');
        });
    }
};
