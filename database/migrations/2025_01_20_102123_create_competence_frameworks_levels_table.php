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
        Schema::create('competence_frameworks_levels', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('competence_framework_uid');
            $table->string('name');
            $table->string('origin_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competence_frameworks_levels');
    }
};
