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
        Schema::table('automatic_resource_approval_users', function (Blueprint $table) {
            $table->foreign(['user_uid'], 'qvkei_automatic_resource_approval_users_ibfk_1')->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automatic_resource_approval_users', function (Blueprint $table) {
            $table->dropForeign('qvkei_automatic_resource_approval_users_ibfk_1');
        });
    }
};
