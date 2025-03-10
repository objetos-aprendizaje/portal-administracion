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
            $table->uuid('uid')->primary();
            $table->uuid('call_uid')->index('call_uid');
            $table->uuid('educational_program_type_uid')->index('educational_program_type_uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls_educational_program_types');
    }
};
