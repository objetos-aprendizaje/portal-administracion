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
use Illuminate\Foundation\Testing\RefreshDatabase;


class ChangeStatusToEnrollingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testChangesCoursesStatusToEnrolling()
    {
        // Preparar datos de prueba
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'uid' => generate_uuid(),
            'enrolling_start_date' => now()->subDays(1),
            'enrolling_finish_date' => now()->addDays(1),
        ])->latest()->first();

        $statusInscription = CourseStatusesModel::factory()->create([
            'uid' => generate_uuid(),
            'code' => 'INSCRIPTION'
        ])->latest()->first();

        CourseStatusesModel::factory()->create([
            'uid' => generate_uuid(),
            'code' => 'ENROLLING'
        ])->latest()->first();

        $course->status()->associate($statusInscription);
        $course->save();

        // Simular que hay estudiantes
        $students = UsersModel::factory()->count(2)->create(); // Crea 2 estudiantes
        $roles = UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generate_uuid()]);// Crea roles de prueba

        foreach ($students as $student) {
            $student->roles()->attach($roles->uid, ['uid' => generate_uuid()]);
        }

        // Preparar el array para attach
        $attachData = [];
        foreach ($students->toArray() as $student) {
            $attachData[$student['uid']] = ['uid' => Str::uuid()];
        }
        // Usar attach para agregar estudiantes al curso
        $course->students()->attach($attachData);

        // Verificar que los estudiantes se hayan agregado correctamente
        $this->assertCount(2, $course->students);
        // Esperar que la cola de trabajos se llene
        Queue::fake();

        // Ejecutar el comando
        Artisan::call('app:change-status-to-enrolling');

        $course->refresh();

        // Verificar que se enviaron las notificaciones
        Queue::assertPushed(SendEmailJob::class, 2);

        // Verificar que se creó la notificación general
        $this->assertDatabaseHas('general_notifications_automatic', [
            'title' => 'Curso en matriculación',
            'description' => 'El curso <b>' . $course->title . '</b> en el que estás inscrito, ya está en período de matriculación',
        ]);
    }
}
