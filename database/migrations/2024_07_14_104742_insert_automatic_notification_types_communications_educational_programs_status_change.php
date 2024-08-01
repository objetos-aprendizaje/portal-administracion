<?php

use Illuminate\Database\Migrations\Migration;
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
            'name' => 'Cambio de estado de programa formativo',
            'description' => 'Notificación automática enviada cuando el estado de un programa formativo cambia',
            'code' => 'CHANGE_STATUS_EDUCATIONAL_PROGRAM',
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
