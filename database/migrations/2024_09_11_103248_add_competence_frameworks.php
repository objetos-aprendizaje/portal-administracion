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
        Schema::create('competence_frameworks', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->boolean('has_levels')->default(false);
            $table->string('name', 255);
            $table->text('description');
            $table->string('origin_code', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
