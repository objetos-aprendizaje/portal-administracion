<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->uuid('uid')->default(DB::raw('(UUID())'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
