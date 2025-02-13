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
            $table->uuid('uid')->primary();
            $table->uuid('uid_user')->index('qvkei_reset_password_tokens_uid_user_foreign');
            $table->string('token', 100);
            $table->timestamp('expiration_date');
            $table->timestamp('created_at')->useCurrent();
            $table->string('email')->index();
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
