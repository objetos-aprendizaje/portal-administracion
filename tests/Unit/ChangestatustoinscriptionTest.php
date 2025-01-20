<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Bus;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Console\Commands\ChangeStatusToInscription;
use App\Models\GeneralNotificationsAutomaticUsersModel;

class ChangestatustoinscriptionTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    public function testGetAcceptedPublicationCourses()
    {
        // Buscar un estado de curso 'ACCEPTED_PUBLICATION'

        $acceptedStatus = CourseStatusesModel::where('code','ACCEPTED_PUBLICATION')->first();

        // Crear cursos con diferentes fechas y estados
         CoursesModel::factory()->withCourseType()->create([
            'inscription_start_date' => now()->subDays(1),
            'inscription_finish_date' => now()->addDays(1),
            'course_status_uid' => $acceptedStatus->uid,
        ]);

        CoursesModel::factory()->withCourseType()->create([
            'inscription_start_date' => now()->subDays(2),
            'inscription_finish_date' => now()->addDays(2),
            'course_status_uid' => $acceptedStatus->uid,
        ]);

         CoursesModel::factory()->withCourseType()->create([
            'inscription_start_date' => now()->subDays(1),
            'inscription_finish_date' => now()->subDays(1), // Este curso no será ser devuelto
            'course_status_uid' => $acceptedStatus->uid,
        ]);

        // Crear estudiantes
        $student1 = UsersModel::factory()->create()->latest()->first();
        $student2 = UsersModel::factory()->create()->latest()->first();

        $generalautomatictype = AutomaticNotificationTypesModel::where('code', 'NEW_COURSES_NOTIFICATIONS')->first();

        $generalautomatic = GeneralNotificationsAutomaticModel::factory()->create([
            'uid' => generateUuid(),
            'automatic_notification_type_uid' => $generalautomatictype->uid
        ]);

        GeneralNotificationsAutomaticUsersModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $student1->uid,
            'general_notifications_automatic_uid' => $generalautomatic->uid,
            'is_read' => 0
        ]);

        GeneralNotificationsAutomaticUsersModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $student2->uid,
            'general_notifications_automatic_uid' => $generalautomatic->uid,
            'is_read' => 0
        ]);

        // Simula el método getAllStudents
        // $studentsUsers = [$student1, $student2];

        // Ejecutar el comando
        $this->artisan('app:change-status-to-inscription')->assertExitCode(0);
        // Ejecutar el comando
        //Artisan::call('app:change-status-to-inscription');

        // Verificar que se enviaron las notificaciones generales
        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $student1->uid,
        ]);

        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $student2->uid,
        ]);


    }

}
