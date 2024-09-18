<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeStatusToEnrollingEducationalProgramTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testChangesEducationalProgramsStatusToEnrolling()
    {

        // Prepara el estado inicial

        $statusInscription = EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();

        $statusEnrolling = EducationalProgramStatusesModel::where('code', 'ENROLLING')->first();

        // Crear un programa educativo en estado INSCRIPTION
        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'uid' => generate_uuid(),
            'name' => 'Curso de Prueba educational program',
            'description' => 'Descripción del curso',
            'inscription_start_date' => now()->subDay(),
            'enrolling_start_date' => now()->subDays(1),
            'enrolling_finish_date' => now()->addDays(1),
            'educational_program_status_uid' => $statusInscription->uid,
        ]);

        // Simular que hay estudiantes
        $students = UsersModel::factory()->count(2)->create();
       // Crear un array para el attach
        $attachData = [];

        // Generar un uid único para cada relación y preparar el array
        foreach ($students as $student) {
            $attachData[$student->uid] = ['uid' => Str::uuid()]; // Generar un nuevo UUID para cada relación
        }

        // Usar attach para agregar estudiantes al programa educativo
        $educationalProgram->students()->attach($attachData);


        // Esperar que la cola de trabajos se llene
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:change-status-to-enrolling-educational-program');

        // Verificar que el estado del programa educativo ha cambiado
        $educationalProgram->refresh();
        $this->assertEquals($statusEnrolling->uid, $educationalProgram->educational_program_status_uid);

        // Verificar que se enviaron las notificaciones
        Queue::assertPushed(SendEmailJob::class, 2);

        // Verificar que se creó la notificación general
        $this->assertDatabaseHas('general_notifications_automatic', [
            'title' => 'Programa formativo en matriculación',
            'description' => 'El programa formativo <b>' . $educationalProgram->name . '</b> en el que estás inscrito, ya está en período de matriculación',
        ]);
    }
}

