<?php


namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Bus;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\EducationalProgramsModel;
use Illuminate\Notifications\Notifiable;
use App\Services\EmailNotificationsService;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Support\Facades\Notification;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Console\Commands\ChangeStatusToInscription;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Console\Commands\ChangeStatusToInscriptionEducationalProgram;

class ChangeStatusToInscriptionEducationalProgramTest extends TestCase
{

    use RefreshDatabase;
    /** @test */
    public function testSendsNotificationsStudentsAcceptedPrograms()
    {


        // Prepara el estado inicial
        $statusAccepted = EducationalProgramStatusesModel::where('code', 'ACCEPTED_PUBLICATION')->first();

        $statusInscription = EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'uid' => generateUuid(),
            'name' => 'Curso de Prueba',
            'description' => 'Descripción del curso de prueba',
            'inscription_start_date' => now()->subDay(),
            'inscription_finish_date' => now()->addDay(),
            'creator_user_uid' => $user->uid,
            'educational_program_status_uid' => $statusAccepted->uid

        ]);

         // Ejecuta el comando
        Artisan::call('app:change-status-to-inscription-educational-program');

        // Verifica que el estado ha cambiado
        $educationalProgram->refresh();
        $this->assertEquals($statusInscription->uid, $educationalProgram->educational_program_status_uid);

        // Verifica que se ha enviado una notificación general
        $generalNotification = GeneralNotificationsAutomaticModel::first();
        $this->assertNotNull($generalNotification);
        $this->assertEquals("Disponible nuevo programa formativo", $generalNotification->title);
   }
}


