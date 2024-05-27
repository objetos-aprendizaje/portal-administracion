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
        Schema::create('competences', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_multi_select')->nullable();
            $table->string('parent_competence_uid', 36)->nullable()->index();
            $table->string('origin_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competences');
    }
};
