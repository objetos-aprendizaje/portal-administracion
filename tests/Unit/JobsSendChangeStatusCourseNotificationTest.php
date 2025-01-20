<?php
namespace Tests\Unit;

use App\Models\LogsModel;
use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\CourseTypesModel;
use Illuminate\Support\Facades\DB;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use App\Models\AutomaticNotificationTypesModel;
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
            'uid' => generateUuid(),
            'name' => 'COURSE_TYPE_01',
        ])->latest()->first();

        $coursestatuses = CourseStatusesModel::where('code', 'READY_ADD_EDUCATIONAL_PROGRAM')->first();

        // Verifica que el estado del curso exista
        $this->assertNotNull($coursestatuses, 'El estado del curso no existe.');

        $course = CoursesModel::factory()->create([
            'uid' => generateUuid(),
            'creator_user_uid' => $user->uid,
            'course_type_uid' => $typecourse1->uid,
            'course_status_uid' => $coursestatuses->uid,
            'identifier' => 'identifier',
            'featured_big_carrousel_approved' => 0,
            'featured_big_carrousel' => 0,
            'status_reason' => 'por prueba',
        ])->first();

        // Crear el tipo de notificación automática
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'CHANGE_STATUS_COURSE')->first();


        // Generar un UID único para la relación
        $userToSync = [
            [
                "uid" => generateUuid(), // Generar un UID único
                "automatic_notification_type_uid" => $automaticNotificationType->uid,
                "user_uid" => $user->uid,
            ],
        ];

        // Insertar el registro en la tabla de notificaciones automáticas deshabilitadas
        DB::table('user_email_automatic_notification_types_disabled')->insert($userToSync);
        // El usuario tenga habilitada la notificación
        $user->automaticGeneralNotificationsTypesDisabled()->attach([]);


        // Simular la cola de trabajos
        Queue::fake();

        // Ejecutar el método handle
        $handler = new SendChangeStatusCourseNotification($course);
        $handler->handle();

        // Verificar que se haya enviado el trabajo
        Queue::assertPushed(SendEmailJob::class);
    }

}
