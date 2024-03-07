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
        Schema::create('calls_educational_program_types', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('call_uid', 36);
            $table->string('educational_program_type_uid', 36);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_calls_educational_program_types');
    }
};
