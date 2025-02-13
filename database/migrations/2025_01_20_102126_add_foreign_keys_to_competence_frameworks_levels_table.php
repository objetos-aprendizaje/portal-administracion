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
        Schema::table('competence_frameworks_levels', function (Blueprint $table) {
            $table->foreign(['competence_framework_uid'], 'cf_uid_fk')->references(['uid'])->on('competence_frameworks')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competence_frameworks_levels', function (Blueprint $table) {
            $table->dropForeign('cf_uid_fk');
        });
    }
};
