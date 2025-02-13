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
        Schema::create('user_role_relationships', function (Blueprint $table) {
            $table->uuid('uid')->primary();
            $table->uuid('user_uid')->index('qvkei_user_role_relationships_user_uid_foreign');
            $table->uuid('user_role_uid')->index('qvkei_user_role_relationships_user_role_uid_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_role_relationships');
    }
};
