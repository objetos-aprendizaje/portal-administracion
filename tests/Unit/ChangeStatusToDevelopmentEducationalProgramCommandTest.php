<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;

use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Services\KafkaService;
use Illuminate\Support\Carbon;
use App\Models\LmsSystemsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Artisan;
use App\Models\EducationalProgramsModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EducationalProgramsStudentsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeStatusToDevelopmentEducationalProgramCommandTest extends TestCase
{
    use RefreshDatabase;


    /**
     * @test
     * Este test verifica que el comando cambia el estado de los programas formativos a 'PENDING_DECISION'
     * cuando no se alcanza el número mínimo de estudiantes.
     */
    public function testChangesEducationalProgramStatusToPendingDecisionIfMinStudentsNotMet()
    {

        // Crear un programa educativo en estado de inscripción que no cumple con el mínimo requerido de estudiantes
        $statusInscription = EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();
        EducationalProgramStatusesModel::where('code', 'PENDING_DECISION')->first();

        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
            'educational_program_status_uid' => $statusInscription->uid,
            'min_required_students' => 5,
        ]);

        // Asociar 3 estudiantes al programa, menos que el mínimo requerido
        $students = UsersModel::factory()->count(3)->create();

        foreach ($students as $student) {
            $educationalProgram->students()->attach($student, [
                'uid' => generateUuid(),
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]);
        }

        // Ejecutar el comando
        Artisan::call('app:change-status-to-development-educational-program');

        // Refrescar la instancia del programa educativo desde la base de datos
        $educationalProgram->refresh();

        // Verificar que el estado del programa educativo se haya cambiado a 'PENDING_DECISION'
        $this->assertEquals('PENDING_DECISION', $educationalProgram->status->code);
    }

    /**
     * @test
     * Este test verifica que el comando cambia el estado de los programas formativos a 'PENDING_DECISION'
     * cuando el número mínimo de estudiantes se cumple.
     */
    public function testChangesStatusoftheeducationalprogramToDevelopmentIfMinStudentsComplied()
    {
        // Crear un programa educativo en estado de inscripción que cumple con el mínimo requerido de estudiantes
        EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();
        EducationalProgramStatusesModel::where('code', 'DEVELOPMENT')->first();

        $statusEnrolling = EducationalProgramStatusesModel::where('code', 'ENROLLING')->first();

        AutomaticNotificationTypesModel::factory()->create([
            'code'=> 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS'
        ]);


        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'enrolling_start_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(60)->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
            'educational_program_status_uid' => $statusEnrolling->uid,
            'min_required_students' => 5,
        ]);

        // Asociar 5 estudiantes al programa, menos que el mínimo requerido
        $students = UsersModel::factory()->count(6)->create();

        foreach ($students as $student) {
            $educationalProgram->students()->attach($student, [
                'uid' => generateUuid(),
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]);
        }
        // Creamos algunos cursos asociados Eduicational program
        CoursesModel::factory(6)
            ->withCourseStatus()
            ->withCourseType()
            ->create([
                'educational_program_uid' => $educationalProgram->uid,
                'lms_system_uid' => LmsSystemsModel::factory()->create()->first()
            ]);

        // Ejecutar el comando
        Artisan::call('app:change-status-to-development-educational-program');

        // Refrescar la instancia del programa educativo desde la base de datos
        $educationalProgram->refresh();

        // Verificar que el estado del programa educativo se haya cambiado a 'DEVELOPMENT'
        $this->assertEquals('DEVELOPMENT', $educationalProgram->status->code);
    }
}
