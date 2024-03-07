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
        Schema::create('course_edition_relationships', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('relationship_uid', 36);
            $table->string('course_uid', 36);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_edition_relationships');
    }
};
