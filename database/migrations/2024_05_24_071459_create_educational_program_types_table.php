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
        Schema::create('educational_program_types', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('managers_can_emit_credentials')->default(false);
            $table->boolean('teachers_can_emit_credentials')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_program_types');
    }
};
