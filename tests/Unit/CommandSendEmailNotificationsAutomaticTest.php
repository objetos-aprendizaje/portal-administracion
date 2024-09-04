<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Models\EmailNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommandSendEmailNotificationsAutomaticTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testSendEmailNotificationsAutomatic()
    {
        // Preparar datos de prueba
        $user = UsersModel::factory()->create([
            'email' => 'test@example.com',
            'email_notifications_allowed' => true,
        ]);

        // Crear una notificación automática pendiente
        $notification = EmailNotificationsAutomaticModel::factory()->create([
            'uid' => generate_uuid(),
            'sent' => false,
            'user_uid' => $user->uid,
            'subject' => 'Test Subject',
            'parameters' => json_encode(['key' => 'value']), // Ejemplo de parámetros
            'template' => 'notification_template',
        ])->first();


         // Simular que hay trabajos en la cola
        Queue::fake();

        // Act: Ejecutar el comando
        Artisan::call('app:send-email-notifications-automatic');

        // Assert: Verificar que se enviaron las notificaciones
        Queue::assertPushed(SendEmailJob::class, 1);
    }
}
