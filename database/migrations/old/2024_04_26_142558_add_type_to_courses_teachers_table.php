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
        Schema::table('courses_teachers', function (Blueprint $table) {
            $table->enum('type', ['COORDINATOR', 'NO_COORDINATOR'])->default('NO_COORDINATOR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('courses_teachers', function (Blueprint $table) {
        });
    }
};
