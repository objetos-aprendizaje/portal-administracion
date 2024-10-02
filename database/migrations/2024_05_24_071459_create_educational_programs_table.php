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
            $table->uuid('uid', 36)->primary();
            $table->uuid('educational_program_type_uid', 36)->index('qvkei_ep_educational_program_type_uid');
            $table->uuid('call_uid', 36)->nullable();
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
