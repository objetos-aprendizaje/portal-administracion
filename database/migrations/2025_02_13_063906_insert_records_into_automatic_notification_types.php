<?php

use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $automaticNotificationTypes = [
            [
                "name" => "Cursos inscritos",
                "code" => "COURSE_ENROLLMENT_COMMUNICATIONS",
                "description" => "Recibe notificaciones sobre cursos en los que estés inscrito/a, como por ejemplo recordatorios de fechas de inicio y fin, calificaciones, pagos, etc."
            ],
            [
                "name" => "Programas formativos inscritos",
                "code" => "EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS",
                "description" => "Recibe notificaciones sobre programas formativos en los que estés inscrito/a, como por ejemplo recordatorios de fechas de inicio y fin, calificaciones, pagos, etc."
            ],
            [
                "name" => "Nuevos cursos",
                "code" => "NEW_COURSES_NOTIFICATIONS",
                "description" => "Recibe notificaciones sobre cursos nuevos que coincidan con tus preferencias, incluyendo categorías y resultados de aprendizaje específicos que hayas configurado"
            ],
            [
                "name" => "Nuevos programas formativos",
                "code" => "NEW_EDUCATIONAL_PROGRAMS",
                "description" => "Recibe notificaciones sobre programas formativos nuevos que coincidan con tus preferencias, incluyendo categorías y resultados de aprendizaje específicos que hayas configurado"
            ],
            [
                "name" => "Nuevos programas formativos pendientes de revisión",
                "code" => "NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS",
                "description" => "Notificación que se envía a los gestores cuando hay un nuevo recurso educativo pendiente de revisión"
            ],
            [
                "name" => "Cambio de estado de cursos creados",
                "code" => "CHANGE_STATUS_COURSE",
                "description" => "Recibe notificaciones de cambio de estado relativas a los cursos que hayas creado, incluyendo información sobre el nuevo estado y motivo del cambio"
            ],
            [
                "name" => "Nuevos cursos pendientes de revisión",
                "code" => "NEW_COURSES_NOTIFICATIONS_MANAGEMENTS",
                "description" => "Recibe notificaciones de cambio de estado relativas a los programas formativos que hayas creado, incluyendo información sobre el nuevo estado y motivo del cambio"
            ],
            [
                "name" => "Cambio de estado de programas formativos creados",
                "code" => "CHANGE_STATUS_EDUCATIONAL_PROGRAM",
                "description" => "Recibe notificaciones de cambio de estado relativas a los programas formativos que hayas creado, incluyendo información sobre el nuevo estado y motivo del cambio"
            ],
            [
                "name" => "Nuevos programas formativos pendientes de revisión",
                "code" => "NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS",
                "description" => "Recibe una notificación cada vez que un nuevo programa formativo sea creado y esté pendiente de revisión"
            ]
        ];

        foreach($automaticNotificationTypes as $automaticNotificationType) {
            AutomaticNotificationTypesModel::insert([
                'uid' => generateUuid(),
                'name' => $automaticNotificationType['name'],
                'code' => $automaticNotificationType['code'],
                'description' => $automaticNotificationType['description']
            ]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
