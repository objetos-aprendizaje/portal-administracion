<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertCourseStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('course_statuses')->insert([
            [
                'uid' => generate_uuid(),
                'name' => 'Listo para añadir a programa formativo',
                'code' => 'READY_ADD_EDUCATIONAL_PROGRAM',
            ],
            [
                'uid' => generate_uuid(),
                'name' => 'Añadido a programa formativo',
                'code' => 'ADDED_EDUCATIONAL_PROGRAM',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('course_statuses')
            ->whereIn('code', ['READY_ADD_EDUCATIONAL_PROGRAM', 'ADDED_EDUCATIONAL_PROGRAM'])
            ->delete();
    }
}
