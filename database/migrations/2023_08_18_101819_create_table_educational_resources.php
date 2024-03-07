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
        Schema::create('educational_resources', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('image_path')->nullable();
            $table->text('resource_path');
            $table->string('status_uid', 36);
            $table->string('educational_resource_type_uid', 36);
            $table->string('course_uid', 36);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resources');
    }
};
