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
        Schema::table('user_lanes', function (Blueprint $table) {
            $table->foreign(['user_uid'])->references(['uid'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lanes', function (Blueprint $table) {
            $table->dropForeign('user_lanes_user_uid_foreign');
        });
    }
};
