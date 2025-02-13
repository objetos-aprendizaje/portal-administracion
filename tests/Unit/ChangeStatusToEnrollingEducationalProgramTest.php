<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Models\EducationalProgramsModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramStatusesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeStatusToEnrollingEducationalProgramTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testChangesEducationalProgramsStatusToEnrolling()
    {

        $statusInscription = EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();

        $statusEnrolling = EducationalProgramStatusesModel::where('code', 'ENROLLING')->first();

        // Crear un programa educativo en estado INSCRIPTION
        EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'uid' => generateUuid(),
            'name' => 'Curso de Prueba educational program',
            'description' => 'Descripción del curso',
            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(29)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(60)->format('Y-m-d\TH:i'),
            'educational_program_status_uid' => $statusEnrolling->uid,
        ]);

        $educationalProgram2 = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'uid' => generateUuid(),
            'name' => 'Curso de Prueba educational program',
            'description' => 'Descripción del curso',
            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(29)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(60)->format('Y-m-d\TH:i'),
            'educational_program_status_uid' => $statusInscription->uid,
        ]);

        AutomaticNotificationTypesModel:: factory()->create([
            'code' => 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS'
        ]);


        // Simular que hay estudiantes
        $students = UsersModel::factory()->count(2)->create();
        // Crear un array para el attach
        $attachData = [];

        // Generar un uid único para cada relación y preparar el array
        foreach ($students as $student) {
            $attachData[$student->uid] = ['uid' => Str::uuid()]; // Generar un nuevo UUID para cada relación
        }

        foreach ($students as $student) {
            $attachData[$student->uid] = [
                'uid' => Str::uuid(),
                'status' => 'ENROLLED',
                'acceptance_status' => 'INSCRIBED'
            ];
        }

        foreach ($students as $student) {
            $educationalProgram2->students()->attach($student, [
                'uid' => generateUuid(),
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]);
        }

        // Esperar que la cola de trabajos se llene
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:change-status-to-enrolling-educational-program');

        // Verificar que el estado del programa educativo ha cambiado
        $educationalProgram2->refresh();
        $this->assertEquals($statusEnrolling->uid, $educationalProgram2->educational_program_status_uid);

        // Validar que hay programas educativos en estado INSCRIPTION antes del cambio
        EducationalProgramsModel::where('educational_program_status_uid', $statusInscription->uid)->get();

        // Asegurarse de que hay programas educativos para procesar
        // $this->assertTrue($educationalPrograms->count() > 0, 'No hay programas educativos en estado INSCRIPTION para cambiar a ENROLLING.');

    }
}
