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
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->foreign(['uid_user'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->dropForeign('reset_password_tokens_uid_user_foreign');
        });
    }
};
