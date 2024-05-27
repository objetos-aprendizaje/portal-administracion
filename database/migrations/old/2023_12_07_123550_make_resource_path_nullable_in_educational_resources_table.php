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
        Schema::table('educational_resources', function (Blueprint $table) {
            $table->string('resource_path')->nullable()->change();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('educational_resources', function (Blueprint $table) {
            $table->string('resource_path')->nullable(false)->change();
        });
    }

};
