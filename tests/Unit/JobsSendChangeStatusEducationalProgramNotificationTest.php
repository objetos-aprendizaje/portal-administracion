<?php
namespace Tests\Unit;
use Tests\TestCase;

use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\SendChangeStatusEducationalProgramNotification;



class JobsSendChangeStatusEducationalProgramNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function testHandleSavesNotificationAndSendsEmail()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $this->actingAs($user);

        // Datos de prueba
        $educationalProgram = [
            'uid' => generate_uuid(),
            'name' => 'Programa Formativo 1',
            'status' => ['name' => 'Activo'],
            'status_reason' => 'Razón del cambio',
            'creator_user' => [
                'email' => $user->email,
                'uid' => $user->uid,
            ],
            'creator_user_uid' => $user->uid,
        ];

        // Act: Fake the Queue
        Queue::fake(); // Asegúrate de llamar a Queue::fake() antes de ejecutar el trabajo

        // Act: Ejecutar el método handle
        $job = new SendChangeStatusEducationalProgramNotification($educationalProgram);
        $job->handle();

        // Assert: Verificar que la notificación se guardó correctamente
        $this->assertDatabaseHas('general_notifications_automatic', [
            'title' => 'Cambio de estado de programa formativo',
            'description' => "<p>El estado del programa formativo Programa Formativo 1 ha cambiado a Activo.</p><p>Motivo: Razón del cambio</p>",
            'entity_uid' => $educationalProgram['uid'],
            'entity' => 'educational_program_change_status'
        ]);

        $jobs = Queue::pushed(SendEmailJob::class);
        $this->assertCount(1, $jobs, 'El trabajo SendEmailJob no fue encolado.');

        $jobInstance = $jobs[0];

    }
}

