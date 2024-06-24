<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertEnrollingIntoEducationalProgramStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('educational_program_statuses')->insert([
            'uid' => generate_uuid(),
            'name' => 'En matriculación',
            'code' => 'ENROLLING',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('educational_program_statuses')->where('code', '=', 'ENROLLING')->delete();
    }
}
