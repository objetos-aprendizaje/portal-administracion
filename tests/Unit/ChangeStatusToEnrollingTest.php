<?php


namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\ChangeStatusToEnrolling;
use Illuminate\Foundation\Testing\RefreshDatabase;


class ChangeStatusToEnrollingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testChangesCoursesStatusToEnrolling()
    {
        // Preparar datos de prueba
        $statusInscription = CourseStatusesModel::firstOrCreate(['code' => 'INSCRIPTION']);
        $statusEnrolling = CourseStatusesModel::firstOrCreate(['code' => 'ENROLLING']);



        $course = CoursesModel::factory()->withCourseType()->create([
            'enrolling_start_date' => now()->subDays(1),
            'enrolling_finish_date' => now()->addDays(1),
            'course_status_uid' => $statusInscription->uid,
        ]);

        UsersModel::factory()->count(2)->create();
        $students = UsersModel::where('email', '!=', 'admin@admin.com')->get();
        foreach ($students as $student) {
            // Asocia a cada estudiante con el curso
            $course->students()->attach($student->uid, ['uid' => generateUuid()]);
        }

        // Verificar que el curso esté en estado INSCRIPTION antes de ejecutar el método
        $this->assertEquals($statusInscription->uid, $course->course_status_uid);

        // Simular la cola de trabajos
        Queue::fake();

        // Ejecutar el método handle
        $command = new ChangeStatusToEnrolling(); // Cambia esto por el nombre real de tu clase de comando
        $command->handle();

        // Refrescar el curso para obtener su estado actualizado
        $course->refresh();

        // Verificar que el estado del curso se haya cambiado a ENROLLING
        $this->assertEquals($statusEnrolling->uid, $course->course_status_uid);

        // Verificar que se enviaron las notificaciones por correo electrónico
        Queue::assertPushed(SendEmailJob::class, 2);

        // Verificar que se creó la notificación general
        $this->assertDatabaseHas('general_notifications_automatic', [
            'title' => 'Curso en matriculación',
            'description' => 'El curso <b>' . $course->title . '</b> en el que estás inscrito, ya está en período de matriculación',
            'entity_uid' => $course->uid,
        ]);

    }

}
