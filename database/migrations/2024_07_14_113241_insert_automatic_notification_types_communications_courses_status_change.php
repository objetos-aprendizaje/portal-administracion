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
            'uid' => generateUuid(),
            'name' => 'Cambio de estado de curso',
            'description' => 'Notificación automática enviada cuando el estado de un curso cambia.',
            'code' => 'CHANGE_STATUS_COURSE',
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
