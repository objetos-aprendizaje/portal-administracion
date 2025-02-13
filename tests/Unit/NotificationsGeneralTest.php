<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\DB;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use App\Services\EmbeddingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EmailNotificationsModel;
use App\Models\NotificationsTypesModel;
use Illuminate\Support\Facades\Request;
use App\Models\GeneralNotificationsModel;
use Illuminate\Database\Eloquent\Builder;
use App\Models\NotificationsPerUsersModel;
use App\Services\EmailNotificationsService;
use App\Models\UserGeneralNotificationsModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\NotificationsChangesStatusesCoursesModel;
use App\Http\Controllers\Notifications\EmailNotificationsController;

class NotificationsGeneralTest extends TestCase
{

    use RefreshDatabase;
    protected EmailNotificationsController $emailNotification;

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /** @test Obtener Index View Notificaciones por usuario */

    public function testIndexViewWithNotificationsPerUsers()
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
        // Simula notificaciones leídas

        $notificationType = NotificationsTypesModel::create([
            'uid' => generateUuid(),
            'name' => 'Tipos de notificaciones',
            // Agrega otros campos necesarios según tu modelo
        ])->latest()->first();

        $notificationGeneral = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
            'type' => 'USERS'
        ])->first();

        NotificationsPerUsersModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'general_notification_uid' => $notificationGeneral->uid,
        ])->first();

        // Hacer la solicitud GET a la ruta
        $response = $this->get(route('notifications-per-users'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que se devuelva la vista correcta
        $response->assertViewIs('notifications.notifications_per_users.index');

        // Verificar que los datos pasan a la vista
        $response->assertViewHas('page_name', 'Notificaciones por usuarios');
        $response->assertViewHas('page_title', 'Notificaciones por usuarios');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'notifications-per-users');
    }

    /**@test Obtener Error usuario no se encuentra */
    public function testGetNotificationsPerUserNotFound()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create([
            'uid' => generateUuid(),
        ])->first();

        // Verificar si $user es null antes de intentar acceder a sus propiedades
        if (!$user) {
            // Intentar obtener notificaciones para un usuario que no existe
            $response = $this->getJson('/notifications/notifications_per_users/get_notifications/' . $user->uid);

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






    /**
     * @test Insertar Notificaciones generales por roles y usuarios
     */
    public function testInsertGeneralNotificationRole()
    {
        // Crear un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create(['name' => 'Type 1'])->first();

        // Crear un rol válido
        $role = UserRolesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Admin'])->first();

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
     * @test Obtener notificaciones por Uid
     */
    public function testGetGeneralNotificationByUidSuccess()
    {
        // Crea un tipo de notificación en la base de datos
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crea una notificación general en la base de datos
        $notification = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
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
     * @test notificaciones al cambiar estatus del curso
     */
    public function testGetNotificationsChangesStatusesCourses()
    {
        // Crear un usuario autenticado

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear un curso que será referenciado
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        // Crear un estatus de curso que será referenciado
        $courseStatus = CourseStatusesModel::factory()->create()->first();

        // Crear una notificación de ejemplo
        $notification = NotificationsChangesStatusesCoursesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'course_uid' => $course->uid,
            'course_status_uid' => $courseStatus->uid,
            'date' => Carbon::now()->format('Y-m-d\TH:i'),
            'is_read' => true,
        ])->first();

        // Realizar la petición GET a la ruta
        $response = $this->get('/notifications/notifications_statuses_courses/get_notifications_statuses_courses/' . $notification->uid);

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contenga los datos de la notificación
        $response->assertJsonFragment([
            'uid' => $notification->uid,
            'user_uid' => $user->uid,
            'is_read' => true,
        ]);

        // Verificar que la notificación se haya marcado como leída
        $this->assertDatabaseHas('notifications_changes_statuses_courses', [
            'uid' => $notification->uid,
            'is_read' => 1,
        ]);
    }


    /**
     * @test obtener email notificación
     */
    public function testGetEmailNotification()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear un tipo de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear una notificación de correo electrónico de ejemplo
        $emailNotification = EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,

        ]);

        // Realizar la petición GET a la ruta
        $response = $this->get('notifications/email/get_email_notification/' . $emailNotification->uid);

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contenga los datos de la notificación
        $response->assertJsonFragment([
            'uid' => $emailNotification->uid,
            'notification_type_uid' => $notificationType->uid,


        ]);
    }


    /**
     * @test Elimina email notificación
     */
    public function testDeleteEmailNotifications()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        Auth::login($user);

        $notificationType1 = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de correo electrónico de ejemplo
        $emailNotification1 = EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType1->uid,
            'status' => 'FAILED',
        ]);
        $emailNotification2 = EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType1->uid,
            'status' => 'SENT',
        ]);

        // Realizar la petición DELETE a la ruta
        $response = $this->delete('/notifications/email/delete_email_notifications', [
            'uids' => [$emailNotification1->uid, $emailNotification2->uid],
        ]);

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);


        // Verificar que no se haya eliminado la notificación enviada
        $this->assertDatabaseHas('email_notifications', [
            'uid' => $emailNotification2->uid,
        ]);
    }



    /**@test Obtener email notificaciones*/

    public function testGetEmailNotifications()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear tipos de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de correo electrónico de ejemplo
        EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 1',
            'body' => 'Cuerpo de Ejemplo 1',
        ]);

        EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 2',
            'body' => 'Cuerpo de Ejemplo 2',
        ]);

        // Realizar la petición GET a la ruta
        $response = $this->get('notifications/email/get_list_email_notifications');

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);
    }
    /**@test Obtener email notificaciónes con la opción buscar*/
    public function testGetEmailNotificationsWithSearch()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear tipos de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de correo electrónico de ejemplo
        EmailNotificationsModel::factory()->create([
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 1',
            'body' => 'Cuerpo de Ejemplo 1',
        ]);
        EmailNotificationsModel::factory()->create([
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 2',
            'body' => 'Cuerpo de Ejemplo 2',
        ]);

        // Realizar la petición GET a la ruta con búsqueda
        $response = $this->get('notifications/email/get_list_email_notifications?search=Ejemplo 1');

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contenga solo la notificación que coincide con la búsqueda
        $response->assertJsonFragment([
            'subject' => 'Asunto de Ejemplo 1',
        ]);

        // Verificar que no contenga la segunda notificación
        $response->assertJsonMissing([
            'subject' => 'Asunto de Ejemplo 2',
        ]);
    }
    /**@test Obtener email notificaciónes con la opción ordenar*/
    public function testGetEmailNotificationsWithSort()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear tipos de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de correo electrónico de ejemplo
        EmailNotificationsModel::factory()->create([
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'B Asunto',
            'body' => 'Cuerpo de Ejemplo 1',
        ]);
        EmailNotificationsModel::factory()->create([
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'A Asunto',
            'body' => 'Cuerpo de Ejemplo 2',
        ]);

        // Realizar la petición GET a la ruta con ordenación
        $response = $this->get('notifications/email/get_list_email_notifications?sort[0][field]=subject&sort[0][dir]=asc&size=10');

        // Verificar que la respuesta tenga el código 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contenga las notificaciones en el orden correcto
        $data = $response->json();
        $this->assertEquals('A Asunto', $data['data'][0]['subject']);
        $this->assertEquals('B Asunto', $data['data'][1]['subject']);
    }

    /**@test Obtener mensaje de envio de notificaciónes*/
    public function testSaveEmailNotificationThrowsExceptionIfSent()
    {

        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear tipos de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de correo electrónico de ejemplo
        $emailNotification1 = EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 1',
            'body' => 'Cuerpo de Ejemplo 1',
            'type' => 'USERS',
            'status' => 'SENT',

        ]);

        // Simular una solicitud para intentar actualizar la notificación existente
        $response = $this->postJson('notifications/email/save_email_notification', [
            'notification_email_uid' => $emailNotification1->uid,
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 1',
            'body' => 'Cuerpo de Ejemplo 1',
            'type' => 'USERS',
            'users' => [$user->uid],


        ]);

        //Verificar que se lance una excepción
        $response->assertStatus(500)
            ->assertJson(['message' => 'La notificación ya ha sido enviada y no puede ser modificada.']);
    }


    /**@test Obtener notificación*/
    public function testSaveEmailNotificationSent()
    {

        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear tipos de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        // Crear notificaciones de correo electrónico de ejemplo
        $emailNotification1 = EmailNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 1',
            'body' => 'Cuerpo de Ejemplo 1',
            'type' => 'USERS',
            'status' => 'FAILED',

        ]);

        // Simular una solicitud para intentar actualizar la notificación existente
        $response = $this->postJson('notifications/email/save_email_notification', [
            'notification_email_uid' => $emailNotification1->uid,
            'notification_type_uid' => $notificationType->uid,
            'subject' => 'Asunto de Ejemplo 1',
            'body' => 'Cuerpo de Ejemplo 1',
            'type' => 'USERS',
            'users' => [$user->uid],
            'status' => 'FAILED',

        ]);

        //Verificar que se lance una excepción
        $response->assertStatus(200);
    }



    /**
     * @test Obtener error cuando el email de notificacion no se encuentra
     *      */
    public function testGetEmailNotificationNotFound()
    {
        // Crear un usuario autenticado
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Intentar obtener una notificación que no existe
        $response = $this->get('/notifications/email/get_email_notification/' . generateUuid());

        // Verificar que la respuesta tenga el código 406 (Not Acceptable)
        $response->assertStatus(406);
        $response->assertJson(['message' => 'La notificación general no existe']);
    }




    public function testIndexNotificationstypes()
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

        $response = $this->get(route('notifications-types'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.notifications_types.index');
        $response->assertViewHas('page_name', 'Tipos de notificaciones');
        $response->assertViewHas('page_title', 'Tipos de notificaciones');
        $response->assertViewHas('resources');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'notifications-types');
    }

    public function testIndexnotificationsPerUsers()
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

        // Crear tipos de notificación
        $notificationType = NotificationsTypesModel::factory()->create()->first();

        $general_notificactions = GeneralNotificationsModel::factory()->create([
            'uid' => generateUuid(),
            'notification_type_uid' => $notificationType->uid,
        ])->first();

        NotificationsPerUsersModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'general_notification_uid' => $general_notificactions->uid,
        ]);

        $response = $this->get(route('notifications-per-users'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.notifications_per_users.index');
        $response->assertViewHas('page_name', 'Notificaciones por usuarios');
        $response->assertViewHas('page_title', 'Notificaciones por usuarios');
        $response->assertViewHas('resources');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'notifications-per-users');
    }

    public function testIndexEmailNotifications()
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
        // Arrange: Crear algunos tipos de notificaciones
        NotificationsTypesModel::factory()->count(3)->create();

        // Act: Realizar la solicitud GET a la ruta
        $response = $this->get(route('notifications-email'));

        // Assert: Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertViewIs('notifications.email.index');
        $response->assertViewHas('page_name', 'Notificaciones por email');
        $response->assertViewHas('page_title', 'Notificaciones por email');
        $response->assertViewHas('resources');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('tomselect', true);
        $response->assertViewHas('flatpickr', true);
        $response->assertViewHas('submenuselected', 'notifications-email');
    }

    public function testApplySentFilters()
    {

        $notificactiontype1 = NotificationsTypesModel::factory()->create()->first();
        $notificactiontype2 = NotificationsTypesModel::factory()->create()->first();
        // Crea algunos registros de ejemplo en la base de datos
        EmailNotificationsModel::factory()->create(['status' => 'SENT', "notification_type_uid" => $notificactiontype1->uid]);
        EmailNotificationsModel::factory()->create(['status' => 'SENT', "notification_type_uid" => $notificactiontype2->uid]);

        $filters = [
            ['database_field' => 'status', 'value' => 'SENT'],
            ['database_field' => 'notification_types', 'value' => [$notificactiontype1->uid, $notificactiontype2->uid]],
            ['database_field' => 'send_date', 'value' => ['2023-01-01', '2023-12-31']],

        ];

        // Inicializa la consulta base
        $query = EmailNotificationsModel::query()
            ->with('emailNotificationType')
            ->with('roles')
            ->with('users')
            ->join('notifications_types', 'email_notifications.notification_type_uid', '=', 'notifications_types.uid', 'left')
            ->select('email_notifications.*', 'notifications_types.name as notification_type_name');

        // Crea una instancia del servicio necesario
        $emailNotificationsService = new EmailNotificationsService();

        // Crea una instancia del controlador pasando el servicio como argumento
        $controllerInstance = new EmailNotificationsController($emailNotificationsService);

        // Llama al método applyFilters usando reflexión
        $reflection = new \ReflectionClass(EmailNotificationsController::class);
        $method = $reflection->getMethod('applyFilters');

        // Invocar el método en la instancia correcta, pasando $query por referencia
        $method->invokeArgs($controllerInstance, [$filters, &$query]);

        $result = $query->get();

        foreach ($result as $item) {
            $this->assertArrayHasKey('uid', $item);
            $this->assertArrayHasKey('subject', $item);
            $this->assertArrayHasKey('send_date', $item);
        }
    }

    /** test **/
    public function testApplyFiltersWithRolesAndUsers()
    {
        $user1 = UsersModel::factory()->create()->latest()->first();
        $user2 = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user1->roles()->attach($roles->uid, ['uid' => generateUuid()]);
        $user2->roles()->attach($roles->uid, ['uid' => generateUuid()]);
        // Autenticar al usuario
        //Auth::login($user);

        // Define un filtro para roles
        $filtersRoles = [
            [
                'database_field' => 'roles',
                'value' => [$roles->uid, $roles->uid] // IDs de roles que queremos filtrar
            ]
        ];

        // Inicializa la consulta base para roles
        $queryRoles = EmailNotificationsModel::query()
            ->with('emailNotificationType')
            ->with('roles')
            ->with('users')
            ->join('notifications_types', 'email_notifications.notification_type_uid', '=', 'notifications_types.uid', 'left')
            ->select('email_notifications.*', 'notifications_types.name as notification_type_name');

        // Crea una instancia del servicio necesario
        $emailNotificationsService = new EmailNotificationsService();

        // Crea una instancia del controlador pasando el servicio como argumento
        $controllerInstance = new EmailNotificationsController($emailNotificationsService);

        // Llama al método applyFilters usando reflexión para roles
        $reflection = new \ReflectionClass(EmailNotificationsController::class);
        $method = $reflection->getMethod('applyFilters');
  

        $method->invokeArgs($controllerInstance, [$filtersRoles, &$queryRoles]);


        $resultRoles = $queryRoles->get();


        foreach ($resultRoles as $item) {
            foreach ($item->roles as $role) {
                $this->assertContains($role->uid, [$roles->uid, $roles->uid]);
            }
        }

        // Ahora definimos un filtro para usuarios
        $filtersUsers = [
            [
                'database_field' => 'users',
                'value' => [$user1->uid, $user2->uid]
            ]
        ];

        // Inicializa la consulta base para usuarios
        $queryUsers = EmailNotificationsModel::query()
            ->with('emailNotificationType')
            ->with('roles')
            ->with('users')
            ->join('notifications_types', 'email_notifications.notification_type_uid', '=', 'notifications_types.uid', 'left')
            ->select('email_notifications.*', 'notifications_types.name as notification_type_name');


        $method->invokeArgs($controllerInstance, [$filtersUsers, &$queryUsers]);


        $resultUsers = $queryUsers->get();


        foreach ($resultUsers as $item) {
            foreach ($item->users as $user) {
                $this->assertContains($user->uid, [$user1->uid, $user2->uid]);
            }
        }
    }

    public function testGetEmailNotificationTypes()
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

        // Crea algunos tipos de notificaciones en la base de datos
        NotificationsTypesModel::factory()->create(['name' => 'Type 1']);
        NotificationsTypesModel::factory()->create(['name' => 'Type 2']);
        NotificationsTypesModel::factory()->create(['name' => 'Type 3']);

        // Crea una instancia del servicio necesario
        $emailNotificationsService = new EmailNotificationsService();

        // Crea una instancia del controlador pasando el servicio como argumento
        $controllerInstance = new EmailNotificationsController($emailNotificationsService);

        // Llama al método applyFilters usando reflexión para roles
        $reflection = new \ReflectionClass(EmailNotificationsController::class);
        $method = $reflection->getMethod('getEmailNotificationTypes');
        

        // Llama al método y captura la respuesta
        $response = $method->invoke($controllerInstance);

        // Verifica que la respuesta sea correcta
        $this->assertEquals(200, $response->getStatusCode());


        $this->assertIsArray(json_decode($response->getContent(), true));
    }
}
