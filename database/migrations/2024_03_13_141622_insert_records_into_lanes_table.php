<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertRecordsIntoLanesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $records = [
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'FEATURED_COURSES'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'FEATURED_PROGRAMS'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'FEATURED_RESOURCES'],

            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'RECENTS_COURSES'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'RECENTS_PROGRAMS'],
            ['uid' => generate_uuid(), 'active' => 1, 'code' => 'RECENTS_RESOURCES'],
        ];

        DB::table('lanes')->insert($records);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
