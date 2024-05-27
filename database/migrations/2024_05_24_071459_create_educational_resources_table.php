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
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('image_path')->nullable();
            $table->string('resource_path')->nullable();
            $table->string('status_uid', 36)->index('status_uid');
            $table->string('educational_resource_type_uid', 36)->index('educational_resource_type_uid');
            $table->timestamps();
            $table->string('license_type', 200)->nullable();
            $table->enum('resource_way', ['URL', 'FILE'])->default('FILE');
            $table->string('resource_url', 2048)->nullable();
            $table->text('status_reason')->nullable();
            $table->string('creator_user_uid', 36);
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
