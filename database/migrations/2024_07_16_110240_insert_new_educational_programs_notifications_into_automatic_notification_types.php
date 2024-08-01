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
            'name' => 'Nuevos programas formativos',
            'description' => 'Notificación automática sobre nuevos programas formativos de tu interés',
            'code' => 'NEW_EDUCATIONAL_PROGRAMS',
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
