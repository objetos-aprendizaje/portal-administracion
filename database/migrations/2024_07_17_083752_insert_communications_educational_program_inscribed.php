<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('automatic_notification_types')->insert([
            'uid' => generate_uuid(),
            'name' => 'Programas formativos inscritos',
            'description' => 'Notificaciones relativas a los programas formativos en los que estás inscrito',
            'code' => 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
