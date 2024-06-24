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
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dateTime('enrolling_start_date')->nullable();
            $table->dateTime('enrolling_finish_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs', function (Blueprint $table) {
            $table->dropColumn('enrolling_start_date');
            $table->dropColumn('enrolling_finish_date');
        });
    }
};
