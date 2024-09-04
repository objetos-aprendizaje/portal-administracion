<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeStatusToFinishedEducationalProgramCommandTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * @test 
     * Este test verifica que el comando cambia el estado de los programas formativos a 'FINISHED' cuando cumplen con las condiciones.
     */
    public function testChangesEducationalProgramStatusToFinished()
    {
        
        $statusDevelopment =  EducationalProgramStatusesModel::where('code','DEVELOPMENT')->first();

        // Crear un programa educativo en estado 'DEVELOPMENT' que ha finalizado
        $educationalProgram = EducationalProgramsModel::factory()->create([
            'realization_finish_date' => now()->subDay(),
            'educational_program_status_uid' => $statusDevelopment->uid,
        ]);

        // Asociar estudiantes al programa educativo
        $students = UsersModel::factory()->count(3)->create();
        foreach ($students as $student) {
            $educationalProgram->students()->attach($student, [
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED',
                'uid' => generate_uuid(),
            ]);
        }

        // Fake para evitar el envío real de correos y notificaciones
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:change-status-to-finished-educational-program');

        // Refrescar el modelo del programa educativo
        $educationalProgram->refresh();

        // Verificar que el estado del programa educativo se haya cambiado a 'FINISHED'
        $this->assertEquals('FINISHED', $educationalProgram->status->code);

        // Verificar que se haya enviado una notificación general
        $this->assertDatabaseHas('general_notifications_automatic', [
            'entity_uid' => $educationalProgram->uid,            
            'title' => 'Programa formativo finalizado',
        ]);

        // Verificar que se hayan despachado los trabajos de envío de email
        Queue::assertPushed(SendEmailJob::class, 3); // Asegurarse de que se despacharon trabajos para todos los estudiantes
    }

    /** 
     * @test 
     * Este test verifica que no se envían notificaciones si no hay estudiantes o no cumplen las condiciones.
     */
    public function testDoesNotSendNotificationsIfNoStudents()
    {
        // Buscar por code = DEVELOPMENT'
        $statusDevelopment =  EducationalProgramStatusesModel::where('code','DEVELOPMENT')->first();

        // Crear un programa educativo en estado 'DEVELOPMENT' que ha finalizado sin estudiantes
        $educationalProgram = EducationalProgramsModel::factory()->create([
            'realization_finish_date' => now()->subDay(),
            'educational_program_status_uid' => $statusDevelopment->uid,
        ]);

        // Fake para evitar el envío real de correos y notificaciones
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:change-status-to-finished-educational-program');

        // Refrescar el modelo del programa educativo
        $educationalProgram->refresh();

        // Verificar que el estado del programa educativo se haya cambiado a 'FINISHED'
        $this->assertEquals('FINISHED', $educationalProgram->status->code);

        // Verificar que no se haya enviado ninguna notificación
        Queue::assertNotPushed(SendEmailJob::class);
        // $this->assertDatabaseMissing('general_notifications_automatic', [
        //     'entity_uid' => $educationalProgram->uid,
        // ]);
    }
}
