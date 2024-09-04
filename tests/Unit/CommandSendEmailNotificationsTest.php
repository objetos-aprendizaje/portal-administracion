<?php

namespace Tests\Unit;

use Str;
use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Models\EmailNotificationsModel;
use App\Models\NotificationsTypesModel;
use Illuminate\Support\Facades\Artisan;
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
            'uid' => generate_uuid(),
            'sent' => true,
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
            'sent' => true,
        ]);
    }

    /** @test Envio de notification todos los usuarios*/
     public function testHandlesEmailNotificationsCorrectlyAllUsers()
    {
        // Arrange: Crea un usuario y una notificación
        UsersModel::factory()->create(['email_notifications_allowed' => true]);
        $notification = EmailNotificationsModel::factory()->create([
            'sent' => 0,
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => null,
            'type' => 'ALL_USERS', //parea ALL-USERS
        ]);

        // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')->once()->with('parameters_email_service')->andReturn(['smtp' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

        // Act: Ejecuta el comando
        Log::shouldReceive('info')->once()->with('ENVÍO DE EMAILS: ' . now());
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

         // Assert: Verifica que el trabajo de envío de email fue despachado
          Queue::assertPushed(SendEmailJob::class, 1);
        // Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'sent' => true,
        ]);
    }

        /** @test Envio notificación por roles*/
    public function testHandlesEmailNotificationsCorrectlyRoles()
    {
        // Arrange: Crea un usuario y un rol
        $role = UserRolesModel::where('code', 'STUDENT')->first();
        $user = UsersModel::factory()->create([
            'email_notifications_allowed' => true,
        ]);

        $user->roles()->attach($role, ['uid'=>generate_uuid()]); // Asocia el rol al usuario

        // Crea una notificación con tipo 'ROLES'
        $notification = EmailNotificationsModel::factory()->create([
            'sent' => 0,
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => null,
            'type' => 'ROLES',
        ]);
        $notification->roles()->attach($role, ['uid'=>generate_uuid()]);

            // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')->once()->with('parameters_email_service')->andReturn(['smtp' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

        // Act: Ejecuta el comando
        Log::shouldReceive('info')->once()->with('ENVÍO DE EMAILS: ' . now());
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

        // Assert: Verifica que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class, 1);

        // Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'sent' => true,
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

        $user->roles()->attach($role, ['uid'=>generate_uuid()]); // Asocia el rol al usuario

        // Crea una notificación con tipo 'ROLES'
        $notification = EmailNotificationsModel::factory()->create([
            'sent' => 0,
            'body' => 'Test email body',
            'subject' => 'Test email subject',
            'notification_type_uid' => null,
            'type' => 'USERS',
        ]);
        $notification->users()->attach($user, ['uid' => generate_uuid()]);

         // Simula que los parámetros del servidor de email son correctos
        Cache::shouldReceive('get')->once()->with('parameters_email_service')->andReturn(['smtp' => 'smtp.example.com', 'username' => 'user', 'password' => 'pass']);

        // Act: Ejecuta el comando
        Log::shouldReceive('info')->once()->with('ENVÍO DE EMAILS: ' . now());
        $this->artisan('app:send-email-notifications')
            ->assertExitCode(0);

        // Assert: Verifica que el trabajo de envío de email fue despachado
        Queue::assertPushed(SendEmailJob::class, 1);

        // Verifica que la notificación se marcó como enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $notification->uid,
            'sent' => true,
        ]);
    }
}



