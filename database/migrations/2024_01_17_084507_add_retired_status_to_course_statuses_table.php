<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AddRetiredStatusToCourseStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('course_statuses')->insert([
            'uid' => Str::uuid(),
            'name' => 'Retirado',
            'code' => 'RETIRED',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('course_statuses')->where('code', 'RETIRED')->delete();
    }
}
