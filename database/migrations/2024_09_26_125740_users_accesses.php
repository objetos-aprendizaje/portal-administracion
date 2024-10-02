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
        Schema::create('users_accesses', function (Blueprint $table) {
            $table->uuid("uid", 36)->primary();
            $table->uuid('user_uid', 36)->index('qvkei_user_accesses_user_uid_foreign');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
