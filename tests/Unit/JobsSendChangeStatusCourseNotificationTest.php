<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\CourseTypesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use App\Jobs\SendChangeStatusCourseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobsSendChangeStatusCourseNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
        public function setUp(): void {
            parent::setUp();
            $this->withoutMiddleware();
            // Asegúrate de que la tabla 'qvkei_settings' existe
            $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
        }

        /** @test */
    public function testSendsEmailAndSavesNotificationDisabled()
    {
        // Preparar datos de prueba
        $user = UsersModel::factory()->create();

        $typecourse1 = CourseTypesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'COURSE_TYPE_1',
        ])->latest()->first();
        ;

        $coursestatuses = CourseStatusesModel::factory()->create([
            'uid' => generate_uuid(),
            'code' => 'READY_ADD_EDUCATIONAL_PROGRAM',
        ])->latest()->first();
        $course = CoursesModel::factory()->create([
            'uid' => generate_uuid(),
            'creator_user_uid' => $user->uid,
            'course_type_uid' => $typecourse1->uid,
            'course_status_uid' => $coursestatuses->uid,
            'identifier' => 'identifier',
            'featured_big_carrousel_approved' => 0,
            'featured_big_carrousel' => 0,
        ]);

            // Asociar el usuario creador con notificaciones automáticas habilitadas
        $user->automaticEmailNotificationsTypesDisabled()->attach([]);
        $user->automaticGeneralNotificationsTypesDisabled()->attach([]);

        // Simular la cola de trabajos
        Queue::fake();

        // Ejecutar el método handle
        $handler = new SendChangeStatusCourseNotification($course);
        $handler->handle();

        // Verificar que se haya enviado el correo electrónico
        Queue::assertPushed(SendEmailJob::class, function ($job) use ($user, $course) {
            // Usar Reflection para acceder a las propiedades protegidas
            $reflection = new \ReflectionClass($job);
            $email = $reflection->getProperty('email');
            $email->setAccessible(true);
            $parameters = $reflection->getProperty('parameters');
            $parameters->setAccessible(true);

            return $email->getValue($job) === $user->email &&
                $parameters->getValue($job)['course_name'] === $course->title &&
                $parameters->getValue($job)['course_status'] === $course->status->name &&
                $parameters->getValue($job)['reason'] === $course->status_reason;
        });

    }

}
