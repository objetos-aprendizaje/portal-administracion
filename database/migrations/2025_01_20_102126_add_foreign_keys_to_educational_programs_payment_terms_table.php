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
        Schema::table('educational_programs_payment_terms', function (Blueprint $table) {
            $table->foreign(['educational_program_uid'], 'fk_educational_program_uid')->references(['uid'])->on('educational_programs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_programs_payment_terms', function (Blueprint $table) {
            $table->dropForeign('fk_educational_program_uid');
        });
    }
};
