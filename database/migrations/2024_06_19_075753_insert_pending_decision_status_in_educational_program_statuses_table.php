<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class InsertPendingDecisionStatusInEducationalProgramStatusesTable extends Migration
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
            'name' => 'Pendiente de decisión',
            'code' => 'PENDING_DECISION',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('educational_program_statuses')->where('code', 'PENDING_DECISION')->delete();
    }
}
