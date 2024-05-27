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
        Schema::table('calls_educational_program_types', function (Blueprint $table) {
            $table->foreign(['call_uid'], 'qvkei_calls_educational_program_types_ibfk_1')->references(['uid'])->on('calls')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['educational_program_type_uid'], 'qvkei_calls_educational_program_types_ibfk_2')->references(['uid'])->on('educational_program_types')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls_educational_program_types', function (Blueprint $table) {
            $table->dropForeign('qvkei_calls_educational_program_types_ibfk_1');
            $table->dropForeign('qvkei_calls_educational_program_types_ibfk_2');
        });
    }
};
