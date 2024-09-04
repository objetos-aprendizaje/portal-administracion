<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationsTypesModel;
use App\Models\GeneralNotificationsModel;
use App\Models\NotificationsPerUsersModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\NotificationServiceProvider;
use App\Models\DestinationsGeneralNotificationsRolesModel;
use App\Http\Controllers\Notifications\GeneralNotificationsController;



class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }


/**
 * @testdox Genera Notificaciones*/

 public function testIndexReturnsViewWithNotificationsTypes()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
       View::share('general_options', $general_options);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);
        // Preparar datos de prueba
        $notificationType = NotificationsTypesModel::create([
            'uid' => generate_uuid(),
            'name' => 'Tipos de notificaciones',
            // Agrega otros campos necesarios según tu modelo
        ]);

        // Hacer la solicitud GET a la ruta
        $response = $this->get(route('notifications-types'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que se devuelva la vista correcta
        $response->assertViewIs('notifications.notifications_types.index');

        // Verificar que los datos se pasen a la vista
        $response->assertViewHas('notifications_types', function ($types) use ($notificationType) {
            return count($types) === 1 && $types[0]['name'] === $notificationType->name;
        });

        // Verificar otros datos que se pasan a la vista
        $response->assertViewHas('page_name', 'Tipos de notificaciones');
        $response->assertViewHas('page_title', 'Tipos de notificaciones');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'notifications-types');
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

/**
 * @test Valida error en generación de notificaciones generales */
    public function testValidationErrorsGenerateNotificacion()
    {
        $response = $this->postJson('/notifications/general/save_general_notifications', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors']);
   }

/**
 * @test genera notificación email */
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

/**
 * @test Valida error en generación de notificaciones generales */
    public function testReturnsPaginatedNotificationTypes()
    {
        // Crea algunos tipos de notificaciones
        NotificationsTypesModel::factory()->count(5)->create();

        // Realiza la solicitud a la ruta
        $response = $this->get('/notifications/notifications_types/get_list_notification_types');

        // Verifica que la respuesta sea un JSON válido
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'current_page',
                    'data' => [
                        '*' => [
                            'uid',
                            'name',
                        ],
                    ],
                    'last_page',
                    'per_page',
                    'total',
                ]);
    }

/**
 * @test Valida Búsqueda tipo de notificación */
    public function testFiltersNotificationTypesBySearch()
    {
        // Crea tipos de notificaciones
        NotificationsTypesModel::factory()->create(['name' => 'Email Notification']);
        NotificationsTypesModel::factory()->create(['name' => 'SMS Notification']);
        NotificationsTypesModel::factory()->create(['name' => 'Push Notification']);

        // Realiza la solicitud con un término de búsqueda
        $response = $this->get('/notifications/notifications_types/get_list_notification_types?search=Email');

        // Verifica que la respuesta contenga solo los resultados filtrados
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Email Notification']);

    }

/**
 * @test Valida Búsqueda tipo de notificación */
    public function testSortsNotificationTypes()
    {
        // Crea tipos de notificaciones
        NotificationsTypesModel::factory()->create(['name' => 'B Notification']);
        NotificationsTypesModel::factory()->create(['name' => 'A Notification']);
        NotificationsTypesModel::factory()->create(['name' => 'C Notification']);

        // Realiza la solicitud con parámetros de orden
        $response = $this->get('/notifications/notifications_types/get_list_notification_types?sort[0][field]=name&sort[0][dir]=asc&size=10');

        // Verifica que la respuesta esté ordenada correctamente
        $response->assertStatus(200);
        $data = $response->json('data');

        // Asegúrate de que hay al menos 3 elementos en $data
        $this->assertCount(3, $data);

        $this->assertEquals('A Notification', $data[0]['name']);
        $this->assertEquals('B Notification', $data[1]['name']);
        $this->assertEquals('C Notification', $data[2]['name']);
    }

/**
 * @test Error 400 tipo de notificación por uid
 */
    public function testReturns400ANotificationType()
    {

        // Crea un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create();

        // Realiza la solicitud a la ruta
        $response = $this->get('/notifications/notifications_types/get_notification_type/' . $notificationType->uid);

        // Verifica que la respuesta sea correcta
        $response->assertStatus(400);

    }

/**
 * @test Error 406 tipo de notificación no existe
 */
    public function testReturnsErrorForNonexistentNotificationType()
    {
        // Realiza la solicitud a la ruta con un UID que no existe
        $response = $this->get('/notifications/notifications_types/get_notification_type/nonexistent-uid');

        // Verifica que la respuesta sea un error 406
        $response->assertStatus(406)
                ->assertJson(['message' => 'El tipo de curso no existe']);
    }

/**
 * @test  Obtiene tipo de notificación
 */

    public function testReturnsNotificationType()
    {


        // Crea un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Realiza la solicitud a la ruta
        $response = $this->get('/notifications/notifications_types/get_notification_type/' . $notificationType->uid);

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJson([
                     'uid' => $notificationType->uid,
                     'name' => $notificationType->name,
                     // Agrega otros campos que esperas en la respuesta
                 ]);
    }

/**
 * @test  Guarda tipo de notificación
 */
    public function testSaveNotificationTypeSuccess()
    {
        $response = $this->postJson('/notifications/notifications_types/save_notification_type', [
            'name' => 'Nuevo Tipo de Notificación',
            'description' => 'Descripción del nuevo tipo',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Tipo de notificación añadida correctamente',
                 ]);

        $this->assertDatabaseHas('notifications_types', [
            'name' => 'Nuevo Tipo de Notificación',
        ]);
    }
/**
 * @test  Actualiza tipo de notificación
 */
    public function testSaveNotificationTypeUpdateSuccess()
    {
        // Crear un tipo de notificación existente
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        $response = $this->postJson('/notifications/notifications_types/save_notification_type', [
            'notification_type_uid' => $notificationType->uid,
            'name' => 'Tipo Actualizado',
            'description' => 'Descripción actualizada',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Tipo de notificación actualizada correctamente',
                 ]);

        $this->assertDatabaseHas('notifications_types', [
            'uid' => $notificationType->uid,
            'name' => 'Tipo Actualizado',
        ]);
    }

/**
 * @test  Notificación de error tipo de notificación
 */
    public function testSaveNotificationTypeUniqueValidationFail()
    {
        // Crear un tipo de notificación existente
        NotificationsTypesModel::create([
            'uid' => generate_uuid(),
            'name' => 'Tipo Único',
            'description' => 'Descripción del tipo único',
        ]);

        $response = $this->postJson('/notifications/notifications_types/save_notification_type', [
            'name' => 'Tipo Único', // Nombre duplicado
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Hay un error en el formulario',
                     'errors' => [
                         'name' => ['El nombre del tipo ya está en uso.'],
                     ],
                 ]);
    }

/**@test Elimina tipo de notificación */
    public function testDeleteNotificationsTypesSuccess()
    {
        // Crear tipos de notificación
        $notificationType1 = NotificationsTypesModel::factory()->create();
        $notificationType2 = NotificationsTypesModel::factory()->create();

        // Eliminar tipos de notificación
        $response = $this->deleteJson('/notifications/notifications_types/delete_notifications_types', [
            'uids' => [$notificationType1->uid, $notificationType2->uid],
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Tipos de notificación eliminados correctamente',
                 ]);

        $this->assertDatabaseMissing('notifications_types', [
            'uid' => $notificationType1->uid,
        ]);

        $this->assertDatabaseMissing('notifications_types', [
            'uid' => $notificationType2->uid,
        ]);
    }

/**@test Error al eliminar tipo notificación cuando esta vinculado */
    public function testDeleteNotificationsTypesWithLinkedNotificationsFail()
    {
        // Crear un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear una notificación vinculada
        GeneralNotificationsModel::create([
            'uid' => generate_uuid(),
            'title' => 'Title general',
            'description' => 'Description general',
            'start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'notification_type_uid' => $notificationType->uid,
            'content' => 'Notificación vinculada',

        ]);

        // Intentar eliminar el tipo de notificación
        $response = $this->deleteJson('/notifications/notifications_types/delete_notifications_types', [
            'uids' => [$notificationType->uid],
        ]);

        $response->assertStatus(406)
                 ->assertJson([
                     'message' => 'No se pueden eliminar los tipos de notificación porque hay notificaciones vinculadas a ellos',
                 ]);
    }

/**@test Obtener notificación por usuario */
    public function testGetNotificationsPerUsersSuccess()
    {
        // Crear usuarios de prueba
        UsersModel::factory()->count(3)->create()->first();

        // Realizar la solicitud GET
        $response = $this->getJson('/notifications/notifications_per_users/get_list_users');

        // Verificar la respuesta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                            'uid',
                             'first_name',
                             'last_name',
                             'email',
                             // Otros campos que esperas en la respuesta...
                         ],
                     ],
                     'links',
                 ]);
    }

/**@test Obtener notificación por búsqueda de usuario */
    public function testGetNotificationsPerUsersWithSearch()
    {
        $userToSearch= UsersModel::factory()->count(3)->create()->first();

        // Realizar la solicitud GET con búsqueda usando el primer nombre del usuario creado
        $response = $this->getJson('/notifications/notifications_per_users/get_list_users?search=' . $userToSearch->first_name);

        // Verificar que solo se devuelva el usuario que coincide con la búsqueda
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data');

    }
/**
 * @test Obtener Notificacions por usuarios ordenados
 */
    public function testGetNotificationsPerUsersWithSort()
    {
        UsersModel::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);

        // Crear un usuario específico
        UsersModel::factory()->create([
            'first_name' => 'María',
            'last_name' => 'González',
        ]);

        // Realizar la solicitud GET con ordenamiento
        $response = $this->getJson('/notifications/notifications_per_users/get_list_users?sort[0][field]=first_name&sort[0][dir]=asc&size=10');

        // Verificar que los usuarios estén ordenados por nombre
        $response->assertStatus(200)
                 ->assertJsonFragment(['first_name' => 'Juan'])
                 ->assertJsonFragment(['first_name' => 'María']);


    }

/**@test Obtener notificacion por usuario */

    public function testGetNotificationsPerUserSuccess()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear tipos de notificación necesarios
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear una notificación para el usuario con un tipo de notificación válido
        $notification = GeneralNotificationsModel::factory()->create([
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);

        NotificationsPerUsersModel::factory()->create([
            'user_uid' => $user->uid,
            'general_notification_uid' => $notification->uid,
        ]);

        // Realiza la solicitud GET para obtener las notificaciones del usuario
        $response = $this->getJson("/notifications/notifications_per_users/get_notifications/{$user->uid}");

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data') // Asegúrate de que se devuelvan 1 notificación
                ->assertJsonFragment(['title' => $notification->title]);
    }

    /**
     * @test Error400 Notificación Email    */
    public function testGenerateNotificationemailFail400()
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
             'type' => '',
             'end_date' => Carbon::now()->addDays(5)->toDateTimeString(),
             'send_date' => Carbon::now()->addMinutes(5)->toDateTimeString(),
             'notification_type_uid' => $notificationType->uid,
             'roles' => [$userRole->uid],
             'users' => [$admin->uid],
         ];


         $response = $this->postJson('notifications/email/save_email_notification', $data);

         $response->assertStatus(400)
                  ->assertJson([
                      'message' => 'Algunos campos son incorrectos',
                  ]);


     }
    }

    public function testGetEmailNotificationReturns400ForInvalidUid()
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

            // Datos de prueba

            $response = $this->json('GET', 'notifications/email/get_email_notification/non-existe');

            $response->assertStatus(406)
                    ->assertJson(['message' => 'La notificación general no existe']);
        }

    }

    /** @test */



    public function testCreatesHandleRolesWhenIsNewIsTrue()
    {
        $role1 = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $role1_uid = $role1->uid;
        $role2 = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $role2_uid = $role2->uid;
        $request = new Request();
        $request->merge(['roles' => [$role1_uid, $role2_uid]]);

        $notification_general = GeneralNotificationsModel::factory()->create();

        $notificationService = new GeneralNotificationsController();
        $notificationService->handleRoles($request, $notification_general, true);

        // Verificar que los roles se hayan creado
        $this->assertCount(2, DestinationsGeneralNotificationsRolesModel::all());

    }

    /** @test */
    public function test_SyncsRolesIsFalse()

    {
        $role3 = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $role3_uid = $role3->uid;
        $role4 = UserRolesModel::where('code', 'TEACHER')->first();
        $role4_uid = $role4->uid;
        // Crear un rol antiguo válido
        $oldRole = UserRolesModel::factory()->create(['code' => 'OLD_ROLEs','uid' => generate_uuid()])->latest()->first();
        $oldRol_Uid = $oldRole->uid;

        $request = new Request();
        $request->merge(['roles' => [$role3_uid, $role4_uid]]);

        // Crear una notificación general simulada
        $notification_general = GeneralNotificationsModel::factory()->create();
        $notification_general->roles()->attach($oldRol_Uid,['uid' => generate_uuid()]);


        // Llamar a la función
        $notificationService = new GeneralNotificationsController();
        $notificationService->handleRoles($request, $notification_general, false);

        // Verificar que los roles nuevos están presentes
        $newRoles = DestinationsGeneralNotificationsRolesModel::where('general_notification_uid', $notification_general->uid)->get();
        $this->assertNotEmpty($newRoles);
        // Verificar que el rol antiguo no está presente
        $oldRole = DestinationsGeneralNotificationsRolesModel::where('rol_uid', $oldRol_Uid)->first();
        $this->assertEmpty($oldRole);

    }

    /** @test */
    public function it_syncs_users_when_isNew_is_false()
    {
        // Crear usuarios válidos
        $user1 = UsersModel::factory()->create();
        $user2 = UsersModel::factory()->create();

        // Crear la solicitud con los usuarios
        $request = new Request();
        $request->merge(['users' => [$user1->uid, $user2->uid]]);

        // Crear una notificación general simulada
        $notification_general = GeneralNotificationsModel::factory()->create();

        // Llamar a la función handleUsers
        $notificationService = new GeneralNotificationsController();
        $notificationService->handleUsers($request, $notification_general, false);

        // Verificar que los usuarios se hayan sincronizado
        $this->assertDatabaseHas('destinations_general_notifications_users', [
            'user_uid' => $user1->uid,
            'general_notification_uid' => $notification_general->uid,
        ]);
        $this->assertDatabaseHas('destinations_general_notifications_users', [
            'user_uid' => $user2->uid,
            'general_notification_uid' => $notification_general->uid,
        ]);

        // Verificar que los roles se hayan desasociado
        $this->assertDatabaseMissing('destinations_general_notifications_roles', [
            'general_notification_uid' => $notification_general->uid,
        ]);
    }



}
