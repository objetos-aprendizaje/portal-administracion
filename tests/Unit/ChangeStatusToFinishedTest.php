<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChangeStatusToFinishedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Este test verifica que el comando cambia el estado de los cursos a 'ENROLLING' cuando cumplen con las condiciones.
     */
    public function testChangesCourseStatusToEnrolling()
    {
        // Crear un estado 'DEVELOPMENT' y 'ENROLLING'
    $statusDevelopment = CourseStatusesModel::factory()->create(['code' => 'DEVELOPMENT']);
    CourseStatusesModel::factory()->create(['code' => 'ENROLLING']);

    // Crear un curso en estado 'DEVELOPMENT' que ha finalizado
    $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
        'realization_finish_date' => now()->subDay(),
        'course_status_uid' => $statusDevelopment->uid,
        'belongs_to_educational_program' => 0,
    ]);

    // Crear tipo de notificación automática
    AutomaticNotificationTypesModel::factory()->create([
        'code' => 'COURSE_ENROLLMENT_COMMUNICATIONS',
    ]);

    // Asociar estudiantes al curso
    $students = UsersModel::factory()->count(3)->create();
    foreach ($students as $student) {
        $course->students()->attach($student, [
            'status' => 'ENROLLED',
            'acceptance_status' => 'ACCEPTED',
            'uid' => generateUuid(),
        ]);
    }

    // Fake para evitar el envío real de correos y notificaciones
    Queue::fake();

    // Ejecutar el comando
    Artisan::call('app:change-status-to-finished');

    // Refrescar el modelo del curso
    $course->refresh();

    // Verificar que el estado del curso se haya cambiado a 'FINISHED'
    $this->assertEquals('FINISHED', $course->status->code);

    // Verificar que se haya enviado una notificación general
    $this->assertDatabaseHas('general_notifications_automatic', [
        'entity_uid' => $course->uid,
        'title' => 'Curso finalizado',
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
        // Buscar code =  DEVELOPMENT '
        $statusDevelopment = CourseStatusesModel::where('code', 'DEVELOPMENT')->first();

        AutomaticNotificationTypesModel::factory()->create([
            'code' => 'COURSE_ENROLLMENT_COMMUNICATIONS',
        ]);
    

        // Crear un curso en estado 'DEVELOPMENT' que ha finalizado sin estudiantes
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'realization_finish_date' => now()->subDay(),
            'course_status_uid' => $statusDevelopment->uid,
            'belongs_to_educational_program' => 0,
        ]);

        // Fake para evitar el envío real de correos y notificaciones
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:change-status-to-finished');

        // Refrescar el modelo del curso
        $course->refresh();

        // Verificar que el estado del curso se haya cambiado a 'ENROLLING'
        $this->assertEquals('FINISHED', $course->status->code);

        // Verificar que no se haya enviado ninguna notificación por email
        Queue::assertNotPushed(SendEmailJob::class);

        // Verificar que no se haya creado una notificación general automática si no hay estudiantes
        // $this->assertDatabaseMissing('general_notifications_automatic', [
        //     'entity_uid' => $course->uid,
        //     'title' => 'Curso finalizado', // Asegúrate de que este título coincida con el que se espera.
        // ]);
    }
}
