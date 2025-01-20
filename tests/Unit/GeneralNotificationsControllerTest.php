<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationsTypesModel;
use App\Models\GeneralNotificationsModel;
use App\Models\UserGeneralNotificationsModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeneralNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    // public function setUp(): void
    // {
    //     parent::setUp();

    //     $this->withoutMiddleware();
    //     $user = UsersModel::factory()->create();
    //     $this->actingAs($user);
    //     $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    // }

    public function testIndexGeneralNotifications()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

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

        NotificationsTypesModel::factory()->count(3)->create();


        $response = $this->get(route('notifications-general'));


        $response->assertStatus(200);
        $response->assertViewIs('notifications.general.index');
        $response->assertViewHas('page_name', 'Notificaciones generales');
        $response->assertViewHas('page_title', 'Notificaciones generales');
        $response->assertViewHas('resources');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('tomselect', true);
        $response->assertViewHas('flatpickr', true);
        $response->assertViewHas('submenuselected', 'notifications-general');
    }

    /**@test Obtener notificación generales  */
    public function testCanGetGeneralNotifications()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);


        // Crear un tipo de notificación necesario
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de prueba con un tipo de notificación válido
        GeneralNotificationsModel::factory()->count(5)->create([
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);

        $response = $this->getJson('/notifications/general/get_list_general_notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'uid',
                        'title',
                        'description',
                        'notification_type_uid',
                        // Agrega otros campos que esperas en la respuesta
                    ],
                ],
                'last_page',
                'total',
            ]);
    }

    /** @test Ordenar notificaciones generales*/
    public function testSortNotificationsGeneral()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);


        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear dos notificaciones generales con el tipo de notificación válido
        GeneralNotificationsModel::factory()->create([
            'title' => 'B Notification',
            'notification_type_uid' => $notificationType->uid
        ]);
        GeneralNotificationsModel::factory()->create([
            'title' => 'A Notification',
            'notification_type_uid' => $notificationType->uid
        ]);

        $response = $this->getJson('/notifications/general/get_list_general_notifications?sort[0][field]=title&sort[0][dir]=asc&size=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'A Notification');
    }

    /** @test */
    public function testFilterNotificationsByType()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);


        $notifica1 = NotificationsTypesModel::factory()->create()->first();
        $notifica2 = NotificationsTypesModel::factory()->create()->first();

        // Crea notificaciones con diferentes tipos
        $generalnotifica1 = GeneralNotificationsModel::factory()->create(['notification_type_uid' => $notifica1->uid]);
        GeneralNotificationsModel::factory()->create(['notification_type_uid' => $notifica2->uid]);

        $response = $this->get('/notifications/general/get_list_general_notifications?filters[0][database_field]=notification_types&filters[0][value][]=' . $generalnotifica1->notification_type_uid);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }


    /** @test  Obtiene filtro de notificaiones por rango*/
    public function testFilterNotificationsByDateRange()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);


        // Crea notificaciones con diferentes fechas
        $notifica1 = NotificationsTypesModel::factory()->create()->first();
        $notifica2 = NotificationsTypesModel::factory()->create()->first();

        // Crea notificaciones con diferentes tipos
        GeneralNotificationsModel::factory()->create([
            'notification_type_uid' => $notifica1->uid,
            'start_date' => Carbon::now()->subDays(5)->format('Y-m-d\TH:i')
        ]);

        GeneralNotificationsModel::factory()->create([
            'notification_type_uid' => $notifica2->uid,
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d\TH:i')
        ]);

        $rol = UserRolesModel::where('code', 'ADMINISTRATOR')->first();


        $filters = [
            ['database_field' => 'start_date', 'value' => now()->subDays(7)->format('Y-m-d\TH:i')],
            ['database_field' => 'end_date', 'value' => now()->addDays(7)->format('Y-m-d\TH:i')],
            ['database_field' => 'roles', 'value' => [$rol->uid]],
        ];

        $queryString = http_build_query(['filters' => $filters]);

        $response = $this->get('/notifications/general/get_list_general_notifications?' . $queryString);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    /**
     * @test Obtener todas las notificaciones con roles
     */
    public function testGetGeneralNotifications()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);
        // Crear un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create(['name' => 'Type 1'])->first();

        // Crear una notificación general
        GeneralNotificationsModel::factory()->create([
            'title' => 'Notification 1',
            'description' => 'Description 1',
            'notification_type_uid' => $notificationType->uid, // Usar el uid del tipo de notificación creado
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'type' => 'USERS'
        ]);

        // Probar la ruta para obtener las notificaciones
        $response = $this->getJson('/notifications/general/get_list_general_notifications?size=1');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertJsonPath('data.0.title', 'Notification 1');
    }

    /** @test */
    public function testSearchNotificationsByTitle()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        $notificactiontype1 = NotificationsTypesModel::factory()->create()->first();
        $notificactiontype2 = NotificationsTypesModel::factory()->create()->first();

        // Crea algunas notificaciones para probar.
        GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación importante',
            //'description' => 'Esta es una descripción importante.',
            'notification_type_uid' => $notificactiontype1->uid,

        ]);

        GeneralNotificationsModel::factory()->create([
            'title' => 'Otra notificación',
            //'description' => 'Descripción secundaria.',
            'notification_type_uid' => $notificactiontype2->uid,
        ]);

        // Realiza la solicitud a la ruta.
        $response = $this->get('/notifications/general/get_list_general_notifications?search=importante');

        // Asegúrate de que la respuesta sea exitosa.
        $response->assertStatus(200);
    }

    /** @test */
    public function testFilterNotificationsByUsers()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);


        // Crea algunos usuarios.
        $user1 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'User One'])->first();
        $user2 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'User Two'])->first();

        $notificactiontype1 = NotificationsTypesModel::factory()->create()->first();
        $notificactiontype2 = NotificationsTypesModel::factory()->create()->first();

        // Crea notificaciones asociadas a los usuarios.
        $notification1 = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación para User One',
            'description' => 'Descripción de la notificación para User One.',
            'notification_type_uid' => $notificactiontype1->uid,

        ]);
        $notification1->users()->attach($user1->uid, ['uid' => generateUuid()]); // Asocia la notificación con el usuario 1.

        $notification2 = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación para User Two',
            'description' => 'Descripción de la notificación para User Two.',
            'notification_type_uid' => $notificactiontype2->uid,
            // otros campos necesarios...
        ]);
        $notification2->users()->attach($user2->uid, ['uid' => generateUuid()]); // Asocia la notificación con el usuario 2.

        // Realiza la solicitud a la ruta con filtro por usuarios.
        $response = $this->get('/notifications/general/get_list_general_notifications?filters[0][database_field]=users&filters[0][value][]=' . $user1->uid);

        // Asegúrate de que la respuesta sea exitosa.
        $response->assertStatus(200);
    }

    public function testFilterNotificationByType()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Crea algunos tipos de notificaciones.
        $notificationType1 = NotificationsTypesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Type A']);
        $notificationType2 = NotificationsTypesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Type B']);

        // Crea notificaciones asociadas a los tipos.
        GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación tipo A',
            'description' => 'Descripción de la notificación tipo A.',
            'notification_type_uid' => $notificationType1->uid,
            'type' => 'ROLES',
        ]);

        GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación tipo B',
            'description' => 'Descripción de la notificación tipo B.',
            'notification_type_uid' => $notificationType2->uid,
            'type' => 'USERS',
        ]);

        // Realiza la solicitud a la ruta con filtro por tipo.
        $response = $this->get('/notifications/general/get_list_general_notifications?filters[0][database_field]=type&filters[0][value]=USERS');

        // Asegúrate de que la respuesta sea exitosa.
        $response->assertStatus(200);
    }


    public function testGetGeneralNotification()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        $type = NotificationsTypesModel::factory()->create();

        $general = GeneralNotificationsModel::factory()->create(
            [
                'notification_type_uid' => $type->uid,
            ]
        );

        $general->roles()->attach($roles->uid, [
            'uid' => generateUuid()
        ]);

        $general->users()->attach($user->uid, [
            'uid' => generateUuid()
        ]);

        // Realiza la solicitud a la ruta con el UID no existente.
        $response = $this->get('/notifications/general/get_general_notification/' . $general->uid);

        // Asegúrate de que la respuesta tenga el código de estado 406.
        $response->assertStatus(200);
    }

    public function testGetGeneralNotificationNotFound()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Genera un UID que no existe en la base de datos.
        $nonExistentUid = '00000000-0000-0000-0000-000000000000';

        // Realiza la solicitud a la ruta con el UID no existente.
        $response = $this->get('/notifications/general/get_general_notification/' . $nonExistentUid);

        // Asegúrate de que la respuesta tenga el código de estado 406.
        $response->assertStatus(406);

        // Verifica que el mensaje de error sea el esperado.
        $response->assertJson(['message' => 'La notificación general no existe']);
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
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid,
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            // Datos de prueba

            $notificationType = new NotificationsTypesModel();
            $notificationType->uid = generateUuid(); // Asigno el uid manualmente
            $notificationType->name = 'Some Notification Type';
            $notificationType->save();
            $notificationType = NotificationsTypesModel::find($notificationType->uid);

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
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        $response = $this->postJson('/notifications/general/save_general_notifications', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function testSaveGeneralNotificationWithExistingUid()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);


        // Autenticar al usuario
        Auth::login($user);

        // Crea algunos tipos de notificaciones.
        $notificationType1 = NotificationsTypesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Type A']);
        // Crea una notificación existente en la base de datos.
        $notification = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación Original',
            'description' => 'Descripción original.',
            'notification_type_uid' => $notificationType1->uid,
        ]);

        // Crea un payload para actualizar la notificación.
        $payload = [
            'notification_general_uid' => $notification->uid, // Usamos el UID existente
            'title' => 'Notificación Actualizada',
            'description' => 'Descripción actualizada.',
            'start_date' => now()->addDays(1)->toISOString(),
            'end_date' => now()->addDays(2)->toISOString(),
            'type' => 'USERS', // Cambia a USERS si es necesario
            'notification_type_uid' => $notificationType1->uid,
            'users' => [$user->uid]
        ];

        $response = $this->postJson('/notifications/general/save_general_notifications', $payload);

        // Asegúrate de que la respuesta tenga el código de estado 200.
        $response->assertStatus(200);

        // Verifica el mensaje de éxito.
        $response->assertJson(['message' => 'Notificación general actualizada correctamente']);

        // Verifica que la notificación se haya actualizado en la base de datos.
        $this->assertDatabaseHas('general_notifications', [
            'uid' => $notification->uid, // Verificamos que se haya actualizado la misma notificación
            'title' => 'Notificación Actualizada',
            'description' => 'Descripción actualizada.',
        ]);
    }

    public function testSaveGeneralNotificationWithRoles()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $roles2 = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Crea algunos tipos de notificaciones.
        $notificationType1 = NotificationsTypesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Type A']);

        // Crea una notificación existente en la base de datos.
        $notification = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación Original',
            'description' => 'Descripción original.',
            'notification_type_uid' => $notificationType1->uid,
        ]);

        // Crea un payload para actualizar la notificación, asegurándote de incluir roles.
        $payload = [
            'notification_general_uid' => $notification->uid, // Usamos el UID existente
            'title' => 'Notificación con Roles',
            'description' => 'Descripción de la notificación con roles.',
            'start_date' => now()->addDays(1)->toISOString(),
            'end_date' => now()->addDays(2)->toISOString(),
            'type' => 'ROLES', // Especificamos que es del tipo ROLES
            'notification_type_uid' => $notificationType1->uid,
            'roles' => [$roles->uid, $roles2->uid],
        ];

        // Realiza la solicitud para guardar la notificación.
        $response = $this->postJson('/notifications/general/save_general_notifications', $payload);

        // Asegúrate de que la respuesta tenga el código de estado 200.
        $response->assertStatus(200);

        // Verifica el mensaje de éxito.
        $response->assertJson(['message' => 'Notificación general actualizada correctamente']);

        // Verifica que la notificación se haya actualizado en la base de datos.
        $this->assertDatabaseHas('general_notifications', [
            'uid' => $notification->uid, // Verificamos que se haya actualizado la misma notificación
            'title' => 'Notificación con Roles',
            'description' => 'Descripción de la notificación con roles.',
        ]);

        // Crea un payload para crear la notificación, asegurándote de incluir roles. Sin notification_general_uid 
        $payload = [
            'title' => 'Notificación con Roles',
            'description' => 'Descripción de la notificación con roles.',
            'start_date' => now()->addDays(1)->toISOString(),
            'end_date' => now()->addDays(2)->toISOString(),
            'type' => 'ROLES', // Especificamos que es del tipo ROLES
            'notification_type_uid' => $notificationType1->uid,
            'roles' => [$roles->uid, $roles2->uid],
        ];

        // Realiza la solicitud para guardar la notificación.
        $response = $this->postJson('/notifications/general/save_general_notifications', $payload);

        // Asegúrate de que la respuesta tenga el código de estado 200.
        $response->assertStatus(200);

        // Verifica el mensaje de éxito.
        $response->assertJson(['message' => 'Notificación general añadida correctamente']);

        // Verifica que la notificación se haya actualizado en la base de datos.
        $this->assertDatabaseHas('general_notifications', [
            'uid' => $notification->uid, // Verificamos que se haya actualizado la misma notificación
            'title' => 'Notificación con Roles',
            'description' => 'Descripción de la notificación con roles.',
        ]);
    }

    /**
     * @test Obtener usuario de notificaciones
     */
    public function testGetUserViewsGeneralNotification()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        // Crear un tipo de notificación antes de crear la notificación general
        $notificationTyp = NotificationsTypesModel::factory()->create()->first();
        // Crear algunas notificaciones para probar

        $generalNotification = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationTyp->uid,
            'start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->format('Y-m-d\TH:i'),
        ]);

        // Crear una entrada en UserGeneralNotificationsModel para el usuario y la notificación
        UserGeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'general_notification_uid' => $generalNotification->uid,

        ]);

        // Hacer la solicitud GET a la ruta
        $response = $this->get('/notifications/general/get_users_views_general_notification/' . $generalNotification->uid);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,

        ]);
    }

    /**
     * @test Obtener notificación medienat búsqueda
     */
    public function testGetUserViewsGeneralNotificationWithSearch()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);

        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        // Crear un tipo de notificación antes de crear la notificación general
        $notificationTyp = NotificationsTypesModel::factory()->create()->first();
        // Crear algunas notificaciones para probar

        $generalNotification = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationTyp->uid,
            'start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->format('Y-m-d\TH:i'),
        ]);

        // Crear una entrada en UserGeneralNotificationsModel para el usuario y la notificación
        UserGeneralNotificationsModel::factory()->create([
            'user_uid' => $user->uid,
            'general_notification_uid' => $generalNotification->uid,
            'view_date' => now(),
        ]);

        // Hacer la solicitud GET a la ruta con un parámetro de búsqueda
        $response = $this->get('/notifications/general/get_users_views_general_notification/' . $generalNotification->uid . '?search=Jane');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);
    }

    public function testGetUserViewsGeneralNotificationWithSorting()
    {

        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        // Crea algunos usuarios en la base de datos.
        $user1 = UsersModel::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
        ]);

        $user2 = UsersModel::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@example.com',
        ]);

        // Crea notificaciones generales y asocia vistas de usuario.
        $generalNotification = GeneralNotificationsModel::factory()->create();

        UserGeneralNotificationsModel::factory()->create([
            'user_uid' => $user1->uid,
            'general_notification_uid' => $generalNotification->uid,
            'view_date' => now(),
        ]);

        UserGeneralNotificationsModel::factory()->create([
            'user_uid' => $user2->uid,
            'general_notification_uid' => $generalNotification->uid,
            'view_date' => now(),
        ]);

        // Realiza la solicitud a la ruta correspondiente.
        $response = $this->getJson('/notifications/general/get_users_views_general_notification/' . $generalNotification->uid . '?sort[0][field]=user_uid&sort[0][dir]=asc&size=10');

        // Asegúrate de que la respuesta tenga el código de estado 200.
        $response->assertStatus(200);
    }

    /**
     * @test Obtener notificacion general por usuario
     */
    public function testGetGeneralNotificationUser()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        $notificationType1 = NotificationsTypesModel::factory()->create()->first();

        // Crear una notificación general
        $generalNotification = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType1->uid,
        ]);

        // Realizar la petición GET a la ruta
        $response = $this->get('/notifications/general/get_general_notification_user/' . $generalNotification->uid);

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contenga los datos de la notificación general
        $response->assertJsonFragment([
            'uid' => $generalNotification->uid,
            'notification_type_uid' => $notificationType1->uid,
        ]);

        // Verificar que se haya creado un registro en UserGeneralNotificationsModel
        $this->assertDatabaseHas('user_general_notifications', [
            'user_uid' => $user->uid,
            'general_notification_uid' => $generalNotification->uid,
        ]);
    }

    /**
     * Notificación general por usuario no existe
     */
    public function testGetGeneralNotificationUserNotFound()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        // Simulamos un UID válido pero inexistente
        $notification_general_uid = '00000000-0000-0000-0000-000000000000';

        // Llamamos al endpoint
        $response = $this->get("/notifications/general/get_general_notification_user/{$notification_general_uid}");

        // Verificamos que se devuelve un código 406 y el mensaje adecuado
        $response->assertStatus(406)
            ->assertJson([
                'message' => 'La notificación general no existe'
            ]);
    }

    /**
     * @test Generación automatica notificaión por usuario
     *
     */
    public function testGetGeneralAutomaticNotificationUser()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        // Crear una notificación automática de ejemplo
        $notification = GeneralNotificationsAutomaticModel::factory()->create()->first();

        $notification->users()->attach($user->uid, [
            'uid' => generateUuid(),
            'is_read' => false,
        ]);

        // Realizar la petición GET a la ruta
        $response = $this->get('/notifications/notifications_statuses_courses/get_general_notification_automatic/' . $notification->uid);

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contenga los datos de la notificación
        $response->assertJsonFragment([
            'uid' => $notification->uid,
        ]);

        // Verificar que la notificación se haya marcado como leída en la tabla pivote
        $this->assertDatabaseHas('general_notifications_automatic_users', [
            'user_uid' => $user->uid,
            'general_notifications_automatic_uid' => $notification->uid,
            'is_read' => true,
        ]);
    }

    /**
     * @test Elimina Notificaciones generales
     */
    public function testDeleteGeneralNotifications()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        // Crear un tipo de notificación antes de crear la notificación general
        $notificationType = NotificationsTypesModel::factory()->create()->first();
        // Crear algunas notificaciones para probar
        $notification1 = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
            'start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->format('Y-m-d\TH:i'),
        ]);


        // Asegurarse de que las notificaciones existan antes de la eliminación
        $this->assertDatabaseCount('general_notifications', 1);

        // Hacer la solicitud DELETE a la ruta
        $response = $this->delete('/notifications/general/delete_general_notifications', [
            'uids' => [$notification1->uid],
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Notificaciones eliminadas correctamente',
            'general_notifications' => [],
        ]);

        // Verificar que las notificaciones hayan sido eliminadas
        $this->assertDatabaseMissing('general_notifications', [
            'uid' => $notification1->uid,
        ]);

        // Verificar que no haya más notificaciones en la base de datos
        $this->assertDatabaseCount('general_notifications', 0);
    }
}
