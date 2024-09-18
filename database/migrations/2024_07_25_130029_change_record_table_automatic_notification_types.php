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
        DB::table('automatic_notification_types')->where('code', 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS')->update(
            [
                'name' => 'Nuevos cursos pendientes de revisión',
                'description' => 'Recibe notificaciones de cambio de estado relativas a los programas formativos que hayas creado, incluyendo información sobre el nuevo estado y motivo del cambio'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'COURSE_ENROLLMENT_TEACHER_COMMUNICATIONS')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
