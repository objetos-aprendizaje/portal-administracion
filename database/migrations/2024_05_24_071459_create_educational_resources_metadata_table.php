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
        Schema::create('educational_resources_metadata', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('educational_resources_uid', 36)->index('fk_educ_resources_meta');
            $table->text('metadata_key');
            $table->text('metadata_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources_metadata');
    }
};
