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
        Schema::create('educational_resource_categories', function (Blueprint $table) {
            $table->uuid('uid', 36)->primary();
            $table->uuid('educational_resource_uid', 36)->index('erc_educational_resource_fk');
            $table->uuid('category_uid', 36)->index('erc_category_fk');
            $table->timestamps();

            $table->unique(['category_uid', 'educational_resource_uid'], 'unique_educational_resource_uid_category_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resource_categories');
    }
};
