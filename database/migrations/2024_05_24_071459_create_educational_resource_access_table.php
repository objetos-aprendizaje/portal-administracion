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
            $table->string('uid', 36)->primary();
            $table->string('user_uid')->nullable();
            $table->string('educational_resource_uid', 36)->index('educational_resource_uid');
            $table->dateTime('date');
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
