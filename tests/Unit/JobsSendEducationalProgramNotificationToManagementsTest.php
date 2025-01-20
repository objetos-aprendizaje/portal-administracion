<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Jobs\SendEducationalProgramNotificationToManagements;

class JobsSendEducationalProgramNotificationToManagementsTest extends TestCase
{

    use RefreshDatabase;

    public function testHandleSendsNotificationEducationalProgram()
    {
        // Datos
        $educationalProgram = [
            'uid' => generateUuid(),
            'name' => 'Programa Educativo 1'
        ];

        // Crear el tipo de notificación si no existe
        AutomaticNotificationTypesModel::factory()->create([
            'uid' => generateUuid(),
            'name' => 'Notificación de nuevos programas educativos para gestión',
            'code' => 'NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS',
        ])->latest()->first();

        // Crea el rol si no existe
        $role = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);

        // Crear usuarios gestores
        $manager1 = UsersModel::factory()->create([
            'email' => 'manager1@example.com'
        ])->latest()->first();
        $manager1->roles()->attach($role->uid, [
            'uid' => generateUuid()]);

        $manager2 = UsersModel::factory()->create([
            'email' => 'manager2@example.com'
        ]);
        $manager2->roles()->attach($role->uid, [
            'uid' => generateUuid()]);

        // Fake the Queue and Notification
        Queue::fake();
        Notification::fake();

        // Ejecutar el trabajo
        $job = new SendEducationalProgramNotificationToManagements($educationalProgram);
        $job->handle();

        // Verificar que la notificación se guardó correctamente
        $this->assertDatabaseHas('general_notifications_automatic', [
            'title' => 'Nuevo programa formativo para revisar',
            'description' => 'Hay un nuevo programa formativo pendiente de revisión',
            'entity_uid' => $educationalProgram['uid']
        ]);

        // Verificar que se han creado las relaciones de notificación con los gestores
        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $manager1->uid
        ]);

        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $manager2->uid
        ]);

        // Verificar que se despachó el trabajo de envío de correo
        Queue::assertPushed(SendEmailJob::class, 2);

    }
}
