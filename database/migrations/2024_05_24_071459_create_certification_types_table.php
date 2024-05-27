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
            $table->string('uid', 36)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->string('category_uid', 36)->nullable()->index('qvkei_certification_types_category_uid_foreign');
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
