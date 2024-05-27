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
        Schema::create('reset_password_tokens', function (Blueprint $table) {
            $table->string('uid', 36)->primary();
            $table->string('uid_user', 36)->index('qvkei_reset_password_tokens_uid_user_foreign');
            $table->string('token', 100);
            $table->dateTime('expiration_date');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reset_password_tokens');
    }
};
