<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Exception;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationsTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;


class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }


 /**
 * @testdox Genera Notificaciones*/
    public function testGenerateNotification()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid,
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {


        // Datos de prueba

        $notificationType= new NotificationsTypesModel();
        $notificationType->uid = '555-12499-123456-12345-99999'; // Asigno el uid manualmente
        $notificationType->name = 'Some Notification Type';
        $notificationType->save();
        $notificationType = NotificationsTypesModel::find('555-12499-123456-12345-99999');

        $data = [
            'title' => 'Test Notification',
            'description' => 'This is a test notification.',
            'start_date' => Carbon::now()->toDateTimeString(),
            'end_date' => Carbon::now()->addDays(5)->toDateTimeString(),
            'type' => 'USERS',
            'notification_type_uid' => $notificationType->uid,
            'users' => [$admin->uid],
        ];

        $response = $this->postJson('/notifications/general/save_general_notifications', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Notificación general añadida correctamente',
                 ]);

        $this->assertDatabaseHas('general_notifications', [
            'title' => 'Test Notification',
            'description' => 'This is a test notification.',
        ]);
    }
    }

    /** @test Valida error en generación de notificaciones generales */
    public function testValidationErrorsGenerateNotificacion()
    {
        $response = $this->postJson('/notifications/general/save_general_notifications', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
   }

   public function testGenerateNotificationemail()
   {
       $admin = UsersModel::factory()->create();
       $roles_bd = UserRolesModel::get()->pluck('uid');
       $roles_to_sync = [];
       foreach ($roles_bd as $rol_uid) {
           $roles_to_sync[] = [
               'uid' => generate_uuid(),
               'user_uid' => $admin->uid,
               'user_role_uid' => $rol_uid
           ];
       }

       $admin->roles()->sync($roles_to_sync);
       $this->actingAs($admin);
       if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
        $userRole = UserRolesModel::where('code', 'STUDENT')->first();
        // Datos de prueba

        // Crea un tipo de notificación necesario para la prueba
        $notificationType= new NotificationsTypesModel();
        $notificationType->uid = '555-12499-123456-12345-33333'; // Asigno el uid manualmente
        $notificationType->name = 'Some Notification Type';
        $notificationType->save();
        $notificationType = NotificationsTypesModel::find('555-12499-123456-12345-33333');



        $data = [
            'subject' => 'Test Email Notification',
            'body' => 'This is a test email notification.',
            'type' => 'ROLES',
            'end_date' => Carbon::now()->addDays(5)->toDateTimeString(),
            'send_date' => Carbon::now()->addMinutes(5)->toDateTimeString(),
            'notification_type_uid' => $notificationType->uid,
            'roles' => [$userRole->uid],
            'users' => [$admin->uid],
        ];


        $response = $this->postJson('notifications/email/save_email_notification', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Notificación por email creada correctamente',
                 ]);

        $this->assertDatabaseHas('email_notifications', [
            'subject' => 'Test Email Notification',
            'body' => 'This is a test email notification.',
        ]);


    }
   }
}

