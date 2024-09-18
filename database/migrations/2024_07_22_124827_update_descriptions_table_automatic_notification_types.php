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
        DB::table('automatic_notification_types')->where('code', 'NEW_EDUCATIONAL_PROGRAMS')->update(
            [
                'name' => 'Nuevos programas formativos',
                'description' => 'Recibe notificaciones sobre programas formativos nuevos que coincidan con tus preferencias, incluyendo categorías y resultados de aprendizaje específicos que hayas configurado'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'NEW_COURSES_NOTIFICATIONS')->update(
            [
                'name' => 'Nuevos cursos',
                'description' => 'Recibe notificaciones sobre cursos nuevos que coincidan con tus preferencias, incluyendo categorías y resultados de aprendizaje específicos que hayas configurado',
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')->update(
            [
                'name' => 'Cursos inscritos',
                'description' => 'Recibe notificaciones sobre cursos en los que estés inscrito/a, como por ejemplo recordatorios de fechas de inicio y fin, calificaciones, pagos, etc.',
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS')->update(
            [
                'name' => 'Nuevos programas formativos pendientes de revisión',
                'description' => 'Recibe una notificación cada vez que un nuevo programa formativo sea creado y esté pendiente de revisión',
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'COURSE_ENROLLMENT_TEACHER_COMMUNICATIONS')->update(
            [
                'name' => 'Comunicaciones sobre cursos inscritos como docente',
                'description' => 'Recibe notificaciones relativas al cambio de estado de cursos creados por tí o en los que figuras como docente',
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'NEW_COURSES_NOTIFICATIONS')->update(
            [
                'name' => 'Nuevos cursos',
                'description' => 'Recibe notificaciones sobre cursos nuevos que coincidan con tus preferencias, incluyendo categorías y resultados de aprendizaje específicos que hayas configurado'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'CHANGE_STATUS_EDUCATIONAL_PROGRAM')->update(
            [
                'name' => 'Cambio de estado de programas formativos creados',
                'description' => 'Recibe notificaciones de cambio de estado relativas a los programas formativos que hayas creado, incluyendo información sobre el nuevo estado y motivo del cambio'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS')->update(
            [
                'name' => 'Programas formativos inscritos',
                'description' => 'Recibe notificaciones sobre programas formativos en los que estés inscrito/a, como por ejemplo recordatorios de fechas de inicio y fin, calificaciones, pagos, etc.'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS')->update(
            [
                'name' => 'Nuevos recursos educativos',
                'description' => 'Recibe notificaciones sobre recursos educativos nuevos que coincidan con tus preferencias, incluyendo categorías y resultados de aprendizaje específicos que hayas configurado'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'CHANGE_STATUS_COURSE')->update(
            [
                'name' => 'Cambio de estado de cursos creados',
                'description' => 'Recibe notificaciones de cambio de estado relativas a los cursos que hayas creado, incluyendo información sobre el nuevo estado y motivo del cambio'
            ]
        );

        DB::table('automatic_notification_types')->where('code', 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS')->update(
            [
                'name' => 'Cambio de estado de cursos creados',
                'description' => 'Recibe una notificación cada vez que un nuevo curso sea creado y esté pendiente de revisión',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
