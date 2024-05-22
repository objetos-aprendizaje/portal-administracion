<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveCenterFromCoursesTable extends Migration
{
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('center');
        });
    }

    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('center')->nullable();
        });
    }
}
