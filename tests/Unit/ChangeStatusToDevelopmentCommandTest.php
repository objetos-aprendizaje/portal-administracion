<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Services\KafkaService;
use App\Models\LmsSystemsModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeStatusToDevelopmentCommandTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * @test
     * Este test verifica que el comando cambia el estado de los cursos a 'DEVELOPMENT' 
     * cuando cumplen con las condiciones necesarias.
     */
    public function testChangesCourseStatusToDevelopment()
    {
        // Crear un LMS System para asociarlo con el curso
        $lmsSystem = LmsSystemsModel::factory()->create()->first();

        // Crear un curso en estado de inscripción que cumple con las condiciones
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'realization_start_date' => now()->subDay(),
            'realization_finish_date' => now()->addDay(),
            'course_status_uid' => CourseStatusesModel::where('code', 'DEVELOPMENT')->first()->uid,
            'min_required_students' => 5,
            'lms_system_uid' => $lmsSystem->uid
        ]);

        // Asociar 5 estudiantes al curso para cumplir con el mínimo requerido
        $students = UsersModel::factory()->count(5)->create();  

        foreach ($students as $student) {
            $course->students()->attach($student, [
                'uid' => generate_uuid(), // Generar un UUID para el campo `uid`
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]);
        }

        // Ejecutar el comando
        Artisan::call('app:change-status-to-development');

        // Verificar que el estado del curso se haya cambiado a 'DEVELOPMENT'
        $this->assertEquals('DEVELOPMENT', $course->fresh()->status->code);
    }

    /** 
     * @test
     * Este test verifica que el comando cambia el estado de los cursos a 'PENDING_DECISION' 
     * cuando no se alcanza el número mínimo de estudiantes.
     */
    public function testChangesCourseStatusToPendingDecisionIfMinStudentsNotMet()
    {
        // Crear un LMS System para asociarlo con el curso
        $lmsSystem = LmsSystemsModel::factory()->create()->first();

        // Crear un curso en estado de inscripción que cumple con las condiciones
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'realization_start_date' => now()->subDay(),
            'realization_finish_date' => now()->addDay(),
            'course_status_uid' => CourseStatusesModel::where('code', 'PENDING_DECISION')->first()->uid,
            'min_required_students' => 5,
            'lms_system_uid' => $lmsSystem->uid
        ]);

        // Asociar 3 estudiantes al curso, menos que el mínimo requerido
        $students = UsersModel::factory()->count(2)->create();

        foreach ($students as $student) {
            $course->students()->attach($student, [
                'uid' => generate_uuid(), // Generar un UUID para el campo `uid`
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]);
        }

        // Ejecutar el comando
        Artisan::call('app:change-status-to-development');

        // Verificar que el estado del curso se haya cambiado a 'PENDING_DECISION'
        $this->assertEquals('PENDING_DECISION', $course->fresh()->status->code);
    }

    /** 
     * @test
     * Este test verifica que el comando cambia el estado de los cursos a 'ENROLLED' 
     * cuando cumplen con las condiciones necesarias.
     */
    public function testChangesCourseStatusToEnrolled()
    {
        // Crear un LMS System para asociarlo con el curso
        $lmsSystem = LmsSystemsModel::factory()->create()->first();

        // Crear un curso en estado de inscripción que cumple con las condiciones
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'realization_start_date' => now()->subDay(),
            'realization_finish_date' => now()->addDay(),
            'course_status_uid' => CourseStatusesModel::where('code', 'ENROLLING')->first()->uid,
            'min_required_students' => 5,
            'lms_system_uid' => $lmsSystem->uid,
            'belongs_to_educational_program' => 0
        ]);

        // Asociar 5 estudiantes al curso para cumplir con el mínimo requerido
        $students = UsersModel::factory()->count(5)->create();  

        foreach ($students as $student) {
            $course->students()->attach($student, [
                'uid' => generate_uuid(), // Generar un UUID para el campo `uid`
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]);
        }        

        // Ejecutar el comando
        Artisan::call('app:change-status-to-development');

        // Verificar que el estado del curso se haya cambiado a 'DEVELOPMENT'
        $this->assertEquals('DEVELOPMENT', $course->fresh()->status->code);
    }

   
}
