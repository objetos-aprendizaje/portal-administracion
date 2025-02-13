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
        Schema::create('user_lanes', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('user_uid')->index('qvkei_user_lanes_user_uid_foreign');
            $table->timestamps();
            $table->boolean('active')->default(true);
            $table->string('code', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_lanes');
    }
};
