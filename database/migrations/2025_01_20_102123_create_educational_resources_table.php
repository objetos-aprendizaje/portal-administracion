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
            $table->uuid('uid')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('image_path')->nullable();
            $table->string('resource_path')->nullable();
            $table->uuid('status_uid')->index('status_uid');
            $table->uuid('educational_resource_type_uid')->index('educational_resource_type_uid');
            $table->timestamps();
            $table->string('resource_url', 2048)->nullable();
            $table->text('status_reason')->nullable();
            $table->uuid('creator_user_uid');
            $table->string('identifier')->nullable();
            $table->enum('resource_way', ['URL', 'FILE', 'IMAGE', 'PDF', 'VIDEO', 'AUDIO'])->default('URL');
            $table->uuid('license_type_uid')->nullable();
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
