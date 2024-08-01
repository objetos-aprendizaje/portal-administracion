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
            $table->string('email')->after('uid')->index(); // Añadir columna email después de id y crear índice
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('reset_password_tokens', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
