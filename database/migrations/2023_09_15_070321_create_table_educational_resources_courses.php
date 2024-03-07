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
        Schema::create('educational_resources_courses', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('educational_resource_uid', 36);
            $table->string('course_uid', 36);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources_courses');
    }
};
