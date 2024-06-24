<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsertDefaultNotificationTypesIntoAutomaticNotificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Inserta los registros en la tabla
        DB::table('automatic_notification_types')->insert([
            [
                'uid' => Str::uuid(),
                'name' => 'Comunicaciones sobre cursos inscritos',
                'code' => 'COURSE_ENROLLMENT_COMMUNICATIONS'
            ],
            [
                'uid' => Str::uuid(),
                'name' => 'Avisos de nuevos cursos',
                'code' => 'NEW_COURSES_NOTIFICATIONS'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('automatic_notification_types')->whereIn('name', [
            'Comunicaciones sobre cursos inscritos',
            'Avisos de nuevos cursos'
        ])->delete();
    }
}
