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
        Schema::create('educational_programs', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->char('educational_program_type_uid', 36)->index('educational_program_type_uid');
            $table->string('call_uid')->nullable();
            $table->string('keywords')->nullable();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('inscription_start_date')->nullable();
            $table->dateTime('inscription_finish_date')->nullable();
            $table->text('image_path')->nullable();
            $table->tinyInteger('is_modular')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_programs');
    }
};
