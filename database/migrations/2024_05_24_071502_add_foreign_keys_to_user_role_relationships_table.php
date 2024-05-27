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
        Schema::table('user_role_relationships', function (Blueprint $table) {
            $table->foreign(['user_role_uid'])->references(['uid'])->on('user_roles')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_role_relationships', function (Blueprint $table) {
            $table->dropForeign('qvkei_user_role_relationships_user_role_uid_foreign');
            $table->dropForeign('qvkei_user_role_relationships_user_uid_foreign');
        });
    }
};
