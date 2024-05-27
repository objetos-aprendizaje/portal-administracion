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
        Schema::table('educational_resources_metadata', function (Blueprint $table) {
            $table->foreign(['educational_resources_uid'], 'fk_educ_resources_meta')->references(['uid'])->on('educational_resources')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_resources_metadata', function (Blueprint $table) {
            $table->dropForeign('fk_educ_resources_meta');
        });
    }
};
