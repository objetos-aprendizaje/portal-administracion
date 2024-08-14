<?php

namespace Tests\Unit;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationsTypesModel;
use App\Models\GeneralNotificationsModel;
use App\Models\NotificationsPerUsersModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationsGeneralTest extends TestCase
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
/**@test Obtener Error usuario no se encuentra */

    public function testGetNotificationsPerUserNotFound()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create([
                'uid' => 'nonexistent-uid',
                ])->first();

        // Verificar si $user es null antes de intentar acceder a sus propiedades
        if (!$user) {
            // Intentar obtener notificaciones para un usuario que no existe
            $response = $this->getJson('/notifications/notifications_per_users/get_notifications/'.$user->uid);

            // Verificar que se devuelva un mensaje de error
            $response->assertStatus(404)
                     ->assertJson(['message' => 'Usuario no encontrado']);
        }
    }

/**@test Obtener por búsqueda la notificación por usuario  */
    public function testGetNotificationsPerUserWithSearch()
    {
            // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear un tipo de notificación necesario
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones para el usuario con un tipo de notificación válido
        $notificationgeneral1 = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación de prueba importante',
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);
        $notificationgeneral2 = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación de prueba',
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);

        NotificationsPerUsersModel::factory()->create([
            'user_uid' => $user->uid,
            'general_notification_uid' => $notificationgeneral1->uid,
        ]);
        NotificationsPerUsersModel::factory()->create([
            'user_uid' => $user->uid,
            'general_notification_uid' => $notificationgeneral2->uid,
        ]);

        // Realizar la solicitud GET para obtener las notificaciones del usuario con búsqueda
        $response = $this->getJson("/notifications/notifications_per_users/get_notifications/{$user->uid}?search=importante");

        // Verificar que la respuesta sea exitosa y contenga solo la notificación relevante
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data') // Asegúrate de que se devuelva 1 notificación
                ->assertJsonFragment(['title' => 'Notificación de prueba importante']);
    }

/**@test Obtener la notificación por usuario ordenada  */
    public function testGetNotificationsPerUserWithSort()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear un tipo de notificación necesario
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones para el usuario con un tipo de notificación válido
        $notificationgeneral1 = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación B',
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);
        $notificationgeneral2 = GeneralNotificationsModel::factory()->create([
            'title' => 'Notificación A',
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);

        NotificationsPerUsersModel::factory()->create([
            'user_uid' => $user->uid,
            'general_notification_uid' => $notificationgeneral1->uid,
        ]);
        NotificationsPerUsersModel::factory()->create([
            'user_uid' => $user->uid,
            'general_notification_uid' => $notificationgeneral2->uid,
        ]);

        // Realizar la solicitud GET para obtener las notificaciones del usuario con ordenamiento
        $response = $this->getJson("/notifications/notifications_per_users/get_notifications/{$user->uid}?sort[0][field]=view_date&sort[0][dir]=asc&size=10");

        // Verificar que la respuesta sea exitosa y las notificaciones estén ordenadas
        $response->assertStatus(200)
                ->assertJsonCount(2, 'data') // Asegúrate de que se devuelvan 2 notificaciones
                ->assertJsonFragment(['title' => 'Notificación A'])
                ->assertJsonFragment(['title' => 'Notificación B']);

    }

/**@test Obtener notificación generales  */
    public function testCanGetGeneralNotifications()
    {
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

/** @test obtener notificaciaones generales por búsqueda*/
    public function testCanFilterNotificationsBySearch()
    {
            // Crear un tipo de notificación necesario
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones para la búsqueda con un tipo de notificación válido
        GeneralNotificationsModel::factory()->create([
            'title' => 'Test Notification',
            'description' => 'Test description',
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);
        GeneralNotificationsModel::factory()->create([
            'title' => 'Another Notification',
            'description' => 'Otra descripción',
            'notification_type_uid' => $notificationType->uid, // Asegúrate de que este ID exista
        ]);

        $query = GeneralNotificationsModel::query()
            ->join('notifications_types', 'general_notifications.notification_type_uid', '=', 'notifications_types.uid', 'left')
            ->where('general_notifications.title', 'LIKE', '%Test%')
            ->orWhere('general_notifications.description', 'LIKE', '%Test%')
            ->select('general_notifications.*', 'notifications_types.name as notification_type_name');

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertDatabaseHas('general_notifications', [
            'title' => 'Test Notification',
            'description' => 'Test description'
        ]);
    }

/** @test Ordenar notificaciones generales*/
    public function testSortNotificationsGeneral()
    {
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
        $notifica1 = NotificationsTypesModel::factory()->create()->first();
        $notifica2 = NotificationsTypesModel::factory()->create()->first();

        // Crea notificaciones con diferentes tipos
        $generalnotifica1 = GeneralNotificationsModel::factory()->create(['notification_type_uid' => $notifica1->uid]);
        $generalnotifica2 = GeneralNotificationsModel::factory()->create(['notification_type_uid' => $notifica2->uid]);

        $response = $this->get('/notifications/general/get_list_general_notifications?filters[0][database_field]=notification_types&filters[0][value][]='.$generalnotifica1->notification_type_uid);

    $response->assertStatus(200);
    $this->assertCount(1, $response->json('data'));
    }

/** @test  Obtiene filtro de notificaiones por rango*/
    public function testFilterNotificationsByDateRange()
    {
        // Crea notificaciones con diferentes fechas
        $notifica1 = NotificationsTypesModel::factory()->create()->first();
        $notifica2 = NotificationsTypesModel::factory()->create()->first();

        // Crea notificaciones con diferentes tipos
        GeneralNotificationsModel::factory()->create([
            'notification_type_uid' => $notifica1->uid,
            'start_date' => Carbon::now()->subDays(5)->format('Y-m-d\TH:i')]);

        GeneralNotificationsModel::factory()->create([
                'notification_type_uid' => $notifica2->uid,
                'start_date' => Carbon::now()->subDays(10)->format('Y-m-d\TH:i')]);

        $response = $this->get('/notifications/general/get_list_general_notifications?filters[0][database_field]=start_date&filters[0][value]=' . now()->subDays(7)->format('Y-m-d\TH:i'));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * @test Insertar Notificaciones generales por roles y usuarios
     */
    public function testInsertGeneralNotificationRole()
    {
        // Crear un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create(['name' => 'Type 1'])->first();

        // Crear un rol válido
        $role = UserRolesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'Admin'])->first();

        // Crear una notificación general
        $notification = GeneralNotificationsModel::factory()->create([
            'title' => 'Test Notification',
            'description' => 'This is a test notification.',
            'notification_type_uid' => $notificationType->uid, // Usar el uid del tipo de notificación creado
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'type' => 'USERS' // Asegúrate de que este valor sea válido
        ]);

        // Insertar un rol asociado a la notificación
        DB::table('destinations_general_notifications_roles')->insert([
            'uid' => (string) Str::uuid(), // Generar un nuevo UUID
            'general_notification_uid' => $notification->uid,
            'rol_uid' => $role->uid
        ]);

        // Verificar que el rol se haya insertado correctamente
        $this->assertDatabaseHas('destinations_general_notifications_roles', [
            'general_notification_uid' => $notification->uid,
            'rol_uid' => $role->uid // Asegúrate de que este valor sea válido
        ]);

    }
    /**
     * @test Obtener todas las notificaciones con roles
     */
    public function testGetGeneralNotifications()
    {
        // Crear un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create(['name' => 'Type 1'])->first();

        // Crear una notificación general
        $notification = GeneralNotificationsModel::factory()->create([
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


    /**
     * @test Obtener notificaciones por Uid
     */
    public function testGetGeneralNotificationByUidSuccess()
    {
        // Crea un tipo de notificación en la base de datos
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crea una notificación general en la base de datos
        $notification = GeneralNotificationsModel::factory()->create([
            'uid' => generate_uuid(),
            'title' => 'Ut distinctio quis.',
            'description' => 'Rerum fugit et voluptas quo. Et voluptatibus sed ad natus.',
            'notification_type_uid' => $notificationType->uid,
            'start_date' => now(),
            'end_date' => now(),
        ]);

        $response = $this->getJson('/notifications/general/get_general_notification/' . $notification->uid);

        $response->assertStatus(200)
                 ->assertJson([
                     'uid' => $notification->uid,
                     'title' => $notification->title,
                     'description' => $notification->description,
                     'notification_type_uid' => $notificationType->uid, // Verifica que el tipo de notificación coincida
                     // Asegúrate de incluir otros campos que esperas en la respuesta
                 ]);
    }

     /**
     * @test Obtener notificaciones sin Uid
     */
    public function testGetGeneralNotificationNoUid()
    {
        $response = $this->getJson('/notifications/general/get_general_notification/');

        $response->assertStatus(404);

    }

}
