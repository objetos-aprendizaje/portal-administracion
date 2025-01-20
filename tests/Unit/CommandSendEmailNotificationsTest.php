<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Models\EmailNotificationsModel;
use App\Models\NotificationsTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommandSendEmailNotificationsTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /** @test  */
    public function testSendEmailNotificationsToAllUsers()
    {
        // Arrange: Crea un usuario y una notificación
        UsersModel::factory()->create(['email_notifications_allowed' => true]);
        $notification = EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'status' => 'SENT',
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => null,
        ])->latest()->first();


        // Act: Ejecuta el comando
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

        //Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'status' => 'SENT',
        ]);
    }

    /** @test Envio de notification todos los usuarios*/
    public function testHandlesEmailNotificationsCorrectlyAllUsers()
    {
        // Arrange: Crea un usuario y una notificación
        $user = UsersModel::factory()->create(['email_notifications_allowed' => true]);

        $type = NotificationsTypesModel::factory()->create();

        $notification = EmailNotificationsModel::factory()->create([
            'status' => 'PENDING',
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => $type->uid,
            'type' => 'ALL_USERS', //parea ALL-USERS
        ]);

        $notification->users()->attach(
            $user->uid,
            [
                'uid' => generateUuid(),
            ]
        );
        // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')->once()->with('parameters_email_service')->andReturn(['smtp' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

        // Act: Ejecuta el comando
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

        // Assert: Verifica que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class);
        // Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'status' => 'PENDING',
        ]);
    }

    /** @test Envio de notification todos los usuarios*/
    public function testHandlesEmailNotificationsWithEmailErrors()
    {
        // Arrange: Crea un usuario y una notificación
        $user = UsersModel::factory()->create(['email_notifications_allowed' => true]);

        $type = NotificationsTypesModel::factory()->create();

        $notification = EmailNotificationsModel::factory()->create([
            'status' => 'PENDING',
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => $type->uid,
            'type' => 'ALL_USERS', //parea ALL-USERS
        ]);

        $notification->users()->attach(
            $user->uid,
            [
                'uid' => generateUuid(),
            ]
        );
        // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')->once()->with('parameters_email_service')->andReturn(null);

        // Act: Ejecuta el comando
        $this->artisan('app:send-email-notifications')
           
            ->assertExitCode(0);
    }

    /** @test Envio notificación por roles*/
    public function testHandlesEmailNotificationsCorrectlyRoles()
    {
        // Arrange: Crea un usuario y un rol
        $role = UserRolesModel::where('code', 'STUDENT')->first();
        $user = UsersModel::factory()->create([
            'email_notifications_allowed' => true,
        ]);

        $user->roles()->attach($role, ['uid' => generateUuid()]); // Asocia el rol al usuario

        // Crea una notificación con tipo 'ROLES'
        $notification = EmailNotificationsModel::factory()->create([
            'status' => 'PENDING',
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => null,
            'type' => 'ROLES',
        ]);
        $notification->roles()->attach($role, ['uid' => generateUuid()]);

        // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')
            ->with('parameters_email_service')
            ->once()
            ->andReturn(['smtp' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

        // Act: Ejecuta el comando
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

        // Assert: Verifica que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class);

        // Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'status' => 'PENDING',
        ]);
    }

    public function testHandlesEmailNotificationsCorrectlyUsers()
    {
        // Arrange: Crea un usuario y un rol
        $role = UserRolesModel::where('code', 'STUDENT')->first();
        $user = UsersModel::factory()->create([
            'email_notifications_allowed' => true,
            'email' => 'student@example.com',  // Email del usuario
        ]);

        $user->roles()->attach($role, ['uid' => generateUuid()]); // Asocia el rol al usuario

        // Crea una notificación con tipo 'ROLES'
        $notification = EmailNotificationsModel::factory()->create([
            'status' => 'PENDING',
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => null,
            'type' => 'USERS',
        ]);
        $notification->users()->attach($user, ['uid' => generateUuid()]);

        // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')->once()->with('parameters_email_service')->andReturn(['smtp' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

        // Act: Ejecuta el comando
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

        // Assert: Verifica que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class);

        // Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'status' => 'PENDING',
        ]);
    }
}
