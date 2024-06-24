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
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('featured_big_carrousel')->nullable()->change();
            $table->boolean('featured_small_carrousel')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('featured_big_carrousel')->nullable(false)->change();
            $table->boolean('featured_small_carrousel')->nullable(false)->change();
        });
    }
};
