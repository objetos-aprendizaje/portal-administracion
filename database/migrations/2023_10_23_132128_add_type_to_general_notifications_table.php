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
        Schema::table('general_notifications', function (Blueprint $table) {
            $table->enum('type', ['ROLES', 'USERS']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
