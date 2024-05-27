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
        Schema::create('educational_resources_tags', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('educational_resource_uid', 36)->index('educational_resource_uid');
            $table->string('tag', 36);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources_tags');
    }
};
