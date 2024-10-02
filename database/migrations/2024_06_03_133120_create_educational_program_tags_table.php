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
        Schema::create('educational_program_tags', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('educational_program_uid', 36);
            $table->string('tag', 255);
            $table->timestamps();

            $table->foreign('educational_program_uid')->references('uid')->on('educational_programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_program_tags');
    }
};
