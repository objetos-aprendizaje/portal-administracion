<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use App\Models\AutomaticNotificationTypesModel;
use App\Jobs\SendCourseNotificationToManagements;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GeneralNotificationsAutomaticUsersModel;

class JobsSendCourseNotificationToManagementsTest extends TestCase
{
    use RefreshDatabase;

    public function testHandleSendsNotificationAndEmail()
    {
            // Datos
        $course = [
            'uid' => generate_uuid(),
            'title' => 'Curso 1'
        ];

        // Crear el tipo de notificación si no existe
        AutomaticNotificationTypesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Tipo de notificación 1',
            'code' => 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS'
        ]);

        // Crea el rol si no existe
        $role = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);

        // Crear usuarios gestores
        $manager1 = UsersModel::factory()->create([
            'uid' => generate_uuid(),
            'email' => 'manager1@example.com'
        ]);
        $manager1->roles()->attach($role->uid, [
            'uid' => generate_uuid()
        ]);

        $manager2 = UsersModel::factory()->create([
            'uid' => generate_uuid(),
            'email' => 'manager2@example.com'
        ]);
        $manager2->roles()->attach($role->uid, [
            'uid' => generate_uuid()
        ]);

        // Act: Fake the Queue and Notification
        Queue::fake();
        Notification::fake();

        // Act: Ejecutar el trabajo
        $job = new SendCourseNotificationToManagements($course);
        $job->handle();

        // Verificar que la notificación se guardó correctamente
        $this->assertDatabaseHas('general_notifications_automatic', [
            'title' => 'Nuevo curso para revisar',
            'description' => 'Hay un nuevo curso pendiente de revisión',
            'entity_uid' => $course['uid']
        ]);

        // Verificar que se han creado las relaciones de notificación con los gestores
        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $manager1->uid
        ]);

        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $manager2->uid
        ]);

        // Verificar que se despachó el trabajo de envío de correo
        Queue::assertPushed(SendEmailJob::class, 2); // Verifica que se hayan enviado 2 trabajos

        // Verificar que se despacharon los trabajos con los parámetros correctos
        Queue::assertPushed(SendEmailJob::class, function ($job) use ($course, $manager1) {
            // Usar reflexión para acceder a la propiedad protegida
            $reflection = new \ReflectionClass($job);
            $emailProperty = $reflection->getProperty('email');
            $emailProperty->setAccessible(true); // Hacer la propiedad accesible

            $parametersProperty = $reflection->getProperty('parameters');
            $parametersProperty->setAccessible(true); // Hacer la propiedad accesible

            return $emailProperty->getValue($job) === $manager1->email &&
                $parametersProperty->getValue($job)['course_title'] === $course['title'];
        });

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($course, $manager2) {
            // Usar reflexión para acceder a la propiedad protegida
            $reflection = new \ReflectionClass($job);
            $emailProperty = $reflection->getProperty('email');
            $emailProperty->setAccessible(true); // Hacer la propiedad accesible

            $parametersProperty = $reflection->getProperty('parameters');
            $parametersProperty->setAccessible(true); // Hacer la propiedad accesible

            return $emailProperty->getValue($job) === $manager2->email &&
                $parametersProperty->getValue($job)['course_title'] === $course['title'];
        });


    }

}
