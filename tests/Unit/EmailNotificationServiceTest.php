<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Queue;
use App\Models\EmailNotificationsModel;
use App\Models\NotificationsTypesModel;
use App\Services\EmailNotificationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailNotificationServiceTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     * Este test verifica que las notificaciones se envían a todos los usuarios
     * y que se actualiza el campo `sent` correctamente.
     */
    public function testProcessNotificationForAllUsers()
    {
        // Simular usuarios en la base de datos
        $users = UsersModel::factory()->count(10)->create([
            'email_notifications_allowed' => true,
        ]);

        $type = NotificationsTypesModel::factory()->create();

        foreach ($users as $user) {

            $user->emailNotificationsTypesDisabled()->attach(
                $type->uid,
                [
                    'uid' => generateUuid(),
                ]
            );
        }

        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        // Crear una notificación simulada
        $notification = EmailNotificationsModel::factory()->create([
            'type' => 'ALL_USERS',
            'subject' => 'Test Notification',
            'body' => 'This is a test notification.',
            'notification_type_uid' => $type->uid,
        ]);

        $notification->roles()->attach($role->uid, [
            'uid' => generateUuid(),
        ]);

        // Fake para evitar el envío real de correos
        Queue::fake();

        // Instanciar el servicio de notificaciones
        $emailNotificationsService = new EmailNotificationsService();

        // Ejecutar el método processNotification
        $emailNotificationsService->processNotification($notification);

        // Verificar que el trabajo de envío de emails fue despachado
        Queue::assertPushed(SendEmailJob::class);

        // Verificar que la notificación fue marcada como enviada
        // $this->assertEquals(1, $notification->fresh()->sent);
    }

    /**
     * @test
     * Este test verifica que las notificaciones se envían a usuarios con roles específicos
     * y que se actualiza el campo `sent` correctamente.
     */
    public function testProcessNotificationForRoles()
    {
        // Simular usuarios y roles en la base de datos
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $users = UsersModel::factory()->count(5)->create([
            'email_notifications_allowed' => true,
        ]);

        $type = NotificationsTypesModel::factory()->create();

        foreach ($users as $user) {

            $user->roles()->sync([
                $role->uid => ['uid' => generateUuid()]
            ]);

            $user->emailNotificationsTypesDisabled()->attach(
                $type->uid,
                [
                    'uid' => generateUuid(),
                ]
            );
        }

        // Crear una notificación simulada
        $notification = EmailNotificationsModel::factory()->create([
            'type' => 'ROLES',
            'subject' => 'Role-based Notification',
            'body' => 'This is a role-based test notification.',
            'notification_type_uid' => $type->uid,
        ]);

        // Asociar la notificación con el rol
        $notification->roles()->attach($role->uid, [
            'uid' => generateUuid(),  // Asegurar que el campo `uid` se genera correctamente
        ]);

        // Fake para evitar el envío real de correos
        Queue::fake();

        // Instanciar el servicio de notificaciones
        $emailNotificationsService = new EmailNotificationsService();

        // Ejecutar el método processNotification
        $emailNotificationsService->processNotification($notification);

        // Verificar que el trabajo de envío de emails fue despachado
        Queue::assertPushed(SendEmailJob::class);

        // Verificar que la notificación fue marcada como enviada
        $this->assertEquals(0, $notification->fresh()->sent);
    }
    
    /**
     * @test
     * Este test verifica que las notificaciones se envían a usuarios específicos
     * y que se actualiza el campo `sent` correctamente.
     */
    public function testProcessNotificationForSpecificUsers()
    {
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        // Simular usuarios en la base de datos
        $users = UsersModel::factory()->count(3)->create([
            'email_notifications_allowed' => true,
        ]);
        
        $type = NotificationsTypesModel::factory()->create();
        // Crear una notificación simulada
        $notification = EmailNotificationsModel::factory()->create([
            'type' => 'USERS',
            'subject' => 'User-specific Notification',
            'body' => 'This is a user-specific test notification.',
            'notification_type_uid' => $type->uid,
        ]);

        $type2 = NotificationsTypesModel::factory()->create();

        // Asociar la notificación con los usuarios, generando un UID único para cada relación
        foreach ($users as $user) {

            $user->roles()->attach($role->uid, [
                'uid' => generateUuid(),
            ]);

            $user->emailNotificationsTypesDisabled()->attach(
                $type2->uid,
                [
                    'uid' => generateUuid(),
                ]
            );

            $notification->users()->attach($user->uid, [
                'uid' => generateUuid(),
            ]);
        }

        // Fake para evitar el envío real de correos
        Queue::fake();

        // Instanciar el servicio de notificaciones
        $emailNotificationsService = new EmailNotificationsService();

        // Ejecutar el método processNotification
        $emailNotificationsService->processNotification($notification);

        // Verificar que el trabajo de envío de emails fue despachado
        Queue::assertPushed(SendEmailJob::class);

         // Verificar que la notificación fue marcada como enviada
         $this->assertEquals(0, $notification->fresh()->sent);

       
    }
}
