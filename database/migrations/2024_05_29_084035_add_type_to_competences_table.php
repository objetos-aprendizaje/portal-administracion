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
        Schema::table('competences', function (Blueprint $table) {
            $table->string('type')->nullable()->after('origin_code');
        });
    }

    public function down()
    {
        Schema::table('competences', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
