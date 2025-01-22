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
        Schema::create('certification_types', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->string('name');
            $table->string('description', 1000)->nullable();
            $table->timestamps();
            $table->uuid('category_uid')->nullable()->index('qvkei_certification_types_category_uid_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certification_types');
    }
};
