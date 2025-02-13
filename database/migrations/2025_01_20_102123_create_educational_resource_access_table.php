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
        Schema::create('educational_resource_access', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('user_uid')->nullable();
            $table->uuid('educational_resource_uid')->index('educational_resource_uid');
            $table->timestamp('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_resource_access');
    }
};
