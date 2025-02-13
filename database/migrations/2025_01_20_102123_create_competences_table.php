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
            $table->uuid('uid')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->uuid('parent_competence_uid')->nullable()->index();
            $table->string('origin_code')->nullable();
            $table->timestamps();
            $table->string('type')->nullable();
            $table->uuid('competence_framework_uid')->nullable();
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
