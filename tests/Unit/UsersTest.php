<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionMethod;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\DepartmentsModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationsTypesModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Users\ListUsersController;
use App\Http\Controllers\DepartmentsController;


class UsersTest extends TestCase
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
     * @test
     * Verifica que la vista de perfil se carga correctamente con los datos esperados.
     */
    public function testIndexLoadsMyProfileViewWithCorrectData()
    {
        // Compartir la variable de roles manualmente con la vista

        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Obtener todos los roles disponibles en la base de datos
        $roles_bd = UserRolesModel::get()->pluck('uid');

        $roles_to_sync = [];

        // Recorrer los roles y asociarlos al usuario
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[$rol_uid] = [
                'uid' => generateUuid(),
                'user_uid' => $user->uid,
                'user_role_uid' => $rol_uid
            ];
        }
        // Obtener la colección de roles del usuario

        // Sincronizar los roles con el usuario
        $user->roles()->sync($roles_to_sync);

        $roles = $user->roles()->get();

        View::share('roles', $roles);

        // Autenticar al usuario en la prueba
        $this->actingAs($user);

        // Crear tipos de notificaciones y asociarlos al usuario
        $generalNotificationType = NotificationsTypesModel::factory()->create()->first();
        $emailNotificationType = NotificationsTypesModel::factory()->create()->first();

        $user->generalNotificationsTypesDisabled()->attach($generalNotificationType->uid, ['uid' => generateUuid()]);
        $user->emailNotificationsTypesDisabled()->attach($emailNotificationType->uid, ['uid' => generateUuid()]);

        // Obtener tipos de notificaciones automáticas existentes sin intentar crear duplicados
        $automaticGeneralNotificationType = AutomaticNotificationTypesModel::firstOrCreate([
            'name' => 'General Notification',
            'code' => 'GENERAL_NOTIFICATION',
            'uid' => generateUuid(),

        ])->first();
        $automaticEmailNotificationType = AutomaticNotificationTypesModel::firstOrCreate([
            'name' => 'Email Notification',
            'code' => 'EMAIL_NOTIFICATION',
            'uid' => generateUuid(),
        ])->first();

        // Simular las notificaciones automáticas deshabilitadas por el usuario
        $user->automaticGeneralNotificationsTypesDisabled()->attach($automaticGeneralNotificationType->uid, ['uid' => generateUuid()]);
        $user->automaticEmailNotificationsTypesDisabled()->attach($automaticEmailNotificationType->uid, ['uid' => generateUuid()]);

        // Crear departamentos
        DepartmentsModel::factory()->count(3)->create();

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Realizar la petición a la ruta del perfil
        $response = $this->get(route('my-profile'));

        // Verificar que se carga la vista correcta
        $response->assertViewIs('my_profile.index');

        // Verificar que los datos esperados están presentes en la vista
        $response->assertViewHas('page_name', 'Mi perfil');
        $response->assertViewHas('page_title', 'Mi perfil');
        $response->assertViewHas('resources', [
            "resources/js/my_profile.js",
        ]);
        $response->assertViewHas('coloris', true);

        // Verificar que los tipos de notificaciones y las asociaciones están presentes
        $response->assertViewHas('notification_types', function ($notificationTypes) {
            return count($notificationTypes) > 0;
        });
        $response->assertViewHas('userGeneralNotificationsDisabled', $user->generalNotificationsTypesDisabled->toArray());
        $response->assertViewHas('userEmailNotificationsDisabled', $user->emailNotificationsTypesDisabled->toArray());

        // Verificación ajustada de los tipos de notificaciones automáticas
        $response->assertViewHas('automaticNotificationTypes', function ($automaticNotificationTypes) use ($roles_bd) {
            if ($automaticNotificationTypes->isEmpty()) {
                return true;
            }

            // Verificar que al menos un tipo de notificación tiene alguno de los roles esperados
            foreach ($automaticNotificationTypes as $type) {
                foreach ($roles_bd as $rol_uid) {
                    if ($type->roles->contains('uid', $rol_uid)) {
                        return true;
                    }
                }
            }
            return false;
        });

        $response->assertViewHas('userAutomaticGeneralNotificationsDisabled', $user->automaticGeneralNotificationsTypesDisabled->toArray());
        $response->assertViewHas('userAutomaticEmailNotificationsDisabled', $user->automaticEmailNotificationsTypesDisabled->toArray());
        $response->assertViewHas('departments', function ($departments) {
            return $departments->count() > 0;
        });
    }


    /**
     * @test
     * Prueba que la vista de listado de usuarios se carga correctamente con las variables necesarias.
     */
    public function testIndexViewLoadsWithCorrectData()
    {
        $user = UsersModel::factory()->create();

        // Simular los datos que deben estar disponibles en la vista
        DepartmentsModel::factory()->count(3)->create();

        $departments = DepartmentsModel::get();

        $rol = UserRolesModel::where('code', 'MANAGEMENT')->first(); // Crea roles de prueba

        $user->roles()->attach($rol->uid, ['uid' => generateUuid()]);

        $this->actingAs($user);

        //Listado de todos los roles
        $roles = UserRolesModel::get();

        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();


        $unreadNotifications = 5;

        // Simular los valores de las variables globales que deben pasar a la vista
        View::share('userRoles', $roles);
        View::share('roles', $roles);
        View::share('general_options', $general_options);
        View::share('tooltip_texts', $tooltip_texts);
        View::share('unread_notifications', $unreadNotifications);

        // Realizar la solicitud GET al método index
        $response = $this->get(route('list-users'));

        // Verificar que la respuesta sea exitosa (status 200)
        $response->assertStatus(200);

        // Verificar que la vista contiene las variables esperadas
        $response->assertViewHas('departments', function ($viewDepartments) use ($departments) {
            return $viewDepartments->count() === $departments->count();
        });

        // Verificar que la vista tiene las variables globales que se han compartido
        $response->assertViewHas('userRoles');
        $response->assertViewHas('general_options');
        $response->assertViewHas('tooltip_texts');
        $response->assertViewHas('unread_notifications');

        // Verificar que la vista cargue el archivo JavaScript necesario
        $response->assertViewHas('resources', function ($resources) {
            return in_array('resources/js/users_module/list_users.js', $resources);
        });

        // Verificar que los otros valores, como "page_name" y "page_title", estén presentes
        $response->assertViewHas('page_name', 'Listado de usuarios');
        $response->assertViewHas('page_title', 'Listado de usuarios');
        $response->assertViewHas('submenuselected', 'list-users');
    }



    /**
     * @testdox Crear Usuario Exitoso*/
    public function testCreateUser()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            $userRole = UserRolesModel::where('code', 'STUDENT')->first();
            $userRoleUid = $userRole->uid;

            // Datos de prueba
            $data = [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'nif' => '37980160F',
                'email' => 'juan.perez@example.com',
                'curriculum' => 'Curriculum content',
                'department_uid' => generateUuid(),
                'photo_path' => null,
                'roles' => json_encode([$userRoleUid]),
            ];

            $response = $this->postJson('/users/list_users/save_user', $data);


            // Verificar la respuesta
            $response->assertStatus(200)
                ->assertJson(['message' => 'Se ha creado el usuario correctamente']);

            // Verificar que el usuario fue creado en la base de datos
            $this->assertDatabaseHas('users', [
                'email' => 'juan.perez@example.com',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
            ]);
        }
    }

    /**
     * @testdox Valida error campos obligatorios*/
    public function testValidateRequiredField()
    {

        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            // Datos de prueba con campos obligatorios faltantes
            $data = [
                'first_name' => '',
                'last_name' => '',
                'nif' => '12345678A',
                'email' => 'juan.perez@example.com',
                'curriculum' => 'Curriculum content',
                'department_uid' => generateUuid(),
                'photo_path' => UploadedFile::fake()->image('photo.jpg'),

            ];

            // Realiza la solicitud
            $response = $this->postJson('/users/list_users/save_user', $data);

            // Verifica que la respuesta tenga un estado de error
            $response->assertStatus(400);

            // Verifica que los errores de validación estén presentes
            $response->assertJsonValidationErrors(['first_name', 'last_name', 'roles']);
        }
    }
    /*@test Actualiza users*/
    public function testUpdateUser()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);
        $userRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $departament = DepartmentsModel::factory()->create()->first();
        $userRoleUid = $userRole->uid;
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            //datos a actualizar
            $data = [
                'user_uid' => $admin->uid,
                'first_name' => 'Jose',
                'last_name' => 'Duch',
                'nif' => '60072548K',
                'email' => 'jose.duch@example.com',
                'curriculum' => 'Updated curriculum content',
                'department_uid' => $departament->uid,
                'photo_path' => UploadedFile::fake()->image('photo.jpg'),
                'roles' => json_encode([$userRoleUid]),
            ];

            // Realizar la solicitud POST para actualizar el usuario
            $response = $this->postJson('/users/list_users/save_user', $data);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                ->assertJson(['message' => 'Se ha actualizado el usuario correctamente']);

            // Verificar que el usuario se haya actualizado en la base de datos
            $this->assertDatabaseHas('users', [
                'uid' => $admin->uid,
                'email' => 'jose.duch@example.com',
                'first_name' => 'Jose',
                'last_name' => 'Duch',
            ]);
        }
    }
    /*@test Email Valido*/
    public function testReturnsValidationEmail()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            // Datos de prueba con un email inválido
            $data = [
                'user_uid' => $admin->uid,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'nif' => '12345678A',
                'email' => 'invalid_email', // Email inválido
                'curriculum' => 'Updated curriculum content',
                'department_uid' => 'department-uuid',
                'roles' => json_encode(['role1', 'role2']),
            ];

            // Realizar la solicitud POST para actualizar el usuario
            $response = $this->postJson('/users/list_users/save_user', $data);

            // Verificar que se devuelva un error de validación para el email
            $response->assertStatus(400)
                ->assertJsonValidationErrors('email');
        }
    }

    /** @group Profile */
    /** @test Guarda preferencias*/
    public function testSaveUserPreference()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        NotificationsTypesModel::factory()->create();

        $notificationsType = NotificationsTypesModel::factory()->create()->first();

        $departament = DepartmentsModel::factory()->create()->first();

        $automaticNotificationType = AutomaticNotificationTypesModel::factory()->create()->first();

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            // Crear un archivo falso para la foto
            $photo = \Illuminate\Http\UploadedFile::fake()->image('photo.jpg', 500, 500);

            // Datos de prueba
            $data = [
                'first_name'                                    => 'Juan',
                'last_name'                                     => 'Pérez',
                'nif'                                           => '21843185Q',
                'curriculum'                                    => 'Curriculum content',
                'photo_path'                                    => $photo,
                'department_uid'                                => $departament->uid,
                'photo_path'                                    => null,
                'general_notifications_allowed'                 => true,
                'email_notifications_allowed'                   => false,
                'general_notification_types_disabled'           => json_encode([
                    $notificationsType->uid

                ]),
                'email_notification_types_disabled'             => json_encode([
                    // generateUuid(),
                    // generateUuid(),
                ]),
                'automatic_general_notification_types_disabled' => json_encode([
                    // generateUuid(),
                    // generateUuid(),
                ]),
                'automatic_email_notification_types_disabled'   => json_encode([$automaticNotificationType->uid])
            ];

            $response = $this->post('/my_profile/update', $data);

            $response->assertStatus(200);
            $response->assertJson(['message' => 'Tu perfil se ha actualizado correctamente']);

            $this->assertDatabaseHas('users', [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'nif' => '21843185Q',
                'curriculum' => 'Curriculum content',
                'department_uid' => $departament->uid,
            ]);
        }
    }

    /*@test Obtener lista de usuarios*/
    public function test_get_users_with_basic_pagination()
    {
        // Crea algunos usuarios de ejemplo
        UsersModel::factory()->count(15)->create();

        // Haz una petición GET a la ruta
        $response = $this->get('/users/list_users/get_users');

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que la respuesta sea JSON
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    // Campos esperados en los usuarios
                    'uid',
                    'first_name',
                    'last_name',
                    'email',
                    'nif',
                    'created_at',
                    'updated_at',
                    'roles' => [] // Verifica que se incluyan los roles
                ]
            ],
            'links' => [], // Verifica que haya información de paginación
        ]);

        // Verifica que haya 1 usuario en la primera página (paginación por defecto de 1)
        $this->assertCount(1, $response->json('data'));
    }
    /*@test search users*/
    public function test_get_users_with_search()
    {
        // Crea algunos usuarios de ejemplo con diferentes nombres y emails
        UsersModel::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@example.com']);
        UsersModel::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@example.com']);

        // Haz una petición GET a la ruta con búsqueda por "John"
        $response = $this->get('/users/list_users/get_users?search=John');

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que solo se devuelva el usuario relacionado con "John"
        $this->assertCount(1, $response->json('data'));
    }
    /* @test ordenar usuarios*/
    public function test_get_users_with_sorting()
    {
        // Crea algunos usuarios de ejemplo con diferentes fechas de creación
        UsersModel::factory()->create(['first_name' => 'test']);
        UsersModel::factory()->create(['first_name' => 'unit']);


        // Haz una petición GET a la ruta con ordenamiento por "created_at" descendente
        $response = $this->get('/users/list_users/get_users?sort[0][field]=created_at&sort[0][dir]=desc&size=10');

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);
    }

    /** @test  Opción buscar*/
    public function testSearchUsersByEmail()
    {
        // Crear usuarios de prueba
        UsersModel::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'nif' => '12345678A']);
        UsersModel::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'nif' => '87654321B']);

        // Realizar la solicitud a la ruta de búsqueda
        $response = $this->get('/users/list_users/search_users/jane@example.com');

        // Verificar que la respuesta es correcta y contiene los usuarios esperados
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'jane@example.com']);
        $response->assertJsonMissing(['email' => 'john@example.com']);
    }

    /** @test */
    public function testReturnsEmptyArrayWhenNoUsersFound()
    {
        // Realizar la solicitud a la ruta de búsqueda con un término que no existe
        $response = $this->get('/users/list_users/search_users/nonexistent');

        // Verificar que la respuesta es correcta y que no se encontraron usuarios
        $response->assertStatus(200);
        $response->assertJson([]);
    }


    /** @test Busca usuarios con roles*/
    public function testSearchUsersWithRoles()
    {

        $user = UsersModel::factory()->create()->first();
        $this->actingAs($user);


        // Crear roles de prueba
        $adminRole = UserRolesModel::create(['uid' => generateUuid(), 'name' => 'Administrador', 'code' => 'ADMINISTRATOR'])->latest()->first();
        $teacherRole = UserRolesModel::create(['uid' => generateUuid(), 'name' => 'Profesor', 'code' => 'TEACHER'])->latest()->first();
        $managementRole = UserRolesModel::create(['uid' => generateUuid(), 'name' => 'Gestor', 'code' => 'MANAGEMENT'])->latest()->first();
        $otherRole = UserRolesModel::create(['uid' => generateUuid(), 'name' => 'Otro', 'code' => 'OTHER'])->latest()->first();

        // Crear usuarios de prueba con roles
        $user1 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Johni', 'last_name' => 'Doei', 'email' => 'john@example.com', 'nif' => '12345674A']);
        $user1->roles()->attach($adminRole->uid, ['uid' => generateUuid()]);

        $user2 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Janei', 'last_name' => 'Doei', 'email' => 'janei@example.com', 'nif' => '87654327B']);
        $user2->roles()->attach($teacherRole->uid, ['uid' => generateUuid()]);

        $user3 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com', 'nif' => '11223344C']);
        $user3->roles()->attach($managementRole->uid, ['uid' => generateUuid()]);

        // Usuario sin rol permitido
        $user4 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Bob', 'last_name' => 'Brown', 'email' => 'bob@example.com', 'nif' => '55667788D']);
        $user4->roles()->attach($otherRole->uid, ['uid' => generateUuid()]);

        // Realizar la solicitud a la ruta de búsqueda
        $response = $this->get('/users/list_users/search_users_backend/Johni');

        // Verificar que la respuesta es correcta y contiene los usuarios esperados
        $response->assertStatus(200);
        $response->assertJsonFragment(['first_name' => 'Johni']);
        $response->assertJsonFragment(['last_name' => 'Doei']);
        $response->assertJsonMissing(['first_name' => 'Bob']);
    }
    /** @test Busca usuarios sin roles*/
    public function testSearchUsersNoEnrolled()
    {
        // Crear roles
        $studentRole = UserRolesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Estudiante', 'code' => 'STUDENT'])->latest()->first();

        // Crear cursos
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create()->first();

        // Crear usuarios
        $user1 = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'first_name' => 'Rossel',
            'last_name' => 'Dump',
            'email' => 'Rossel@example.com',
            'nif' => '12345698A',
        ])->first();

        $user1->roles()->attach($studentRole->uid, ['uid' => generateUuid()]);

        $user2 = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'first_name' => 'Mariam',
            'last_name' => 'Poll',
            'email' => 'Mariam@example.com',
            'nif' => '87654331B',
        ])->first();
        $user2->roles()->attach($studentRole->uid, ['uid' => generateUuid()]);

        // Asignar el curso a un usuario
        $user1->coursesStudents()->attach($course->uid, ['uid' => generateUuid()]);

        // Realizar la búsqueda
        $response = $this->get('/users/list_users/search_users_no_enrolled/' . $course->uid . '/Mariam');

        // Afirmaciones
        $response->assertStatus(200);
        $response->assertJsonMissing(['first_name' => 'Rossel']);
    }

    /** @test Busca usuarios no enrolado en Programas educacionales*/
    public function testSearchUsersNoEnrolledEducationalProgram()
    {
        //Datos
        //Crea roles
        $roles1 = UserRolesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Estudiante', 'code' => 'STUDENT'])->latest()->first();

        $educationalProgramTypes = EducationalProgramTypesModel::factory()->create()->first();

        // Crear programa educativo
        $educationalProgram = EducationalProgramsModel::factory()->create([
            'uid' => generateUuid(),
            'educational_program_type_uid' => $educationalProgramTypes->uid

        ]);

        // Crear cursos
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'uid' => generateUuid(),
            'title' => 'Nombre curso2',
            // 'educational_program_type_uid' => $educationalProgramTypes->uid
        ]);


        // Crear usuarios de prueba
        $user1 = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'first_name' => 'Johne',
            'last_name' => 'Doee',
            'email' => 'johne@example.com',
            'nif' => '12345679A'
        ]);
        $user1->roles()->attach($roles1->uid, ['uid' => generateUuid()]);


        $user2 = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'first_name' => 'Janes',
            'last_name' => 'Smithi',
            'email' => 'janes@example.com',
            'nif' => '87654320B'
        ]);
        $user2->roles()->attach($roles1->uid, ['uid' => generateUuid()]);

        // Asociar el usuario 1 a un programa educativo
        DB::table('educational_programs_students')->insert([
            'uid' => generateUuid(),
            'educational_program_uid' => $educationalProgram->uid,
            'user_uid' => $user1->uid,
            'acceptance_status' => 'ACCEPTED',
            'status' => 'ENROLLED'
        ]);
        DB::table('educational_programs_students')->where('educational_program_uid', $educationalProgram->uid)->get();


        $search = 'Janes';
        $response = $this->get("/users/list_users/search_users_no_enrolled_educational_program/{$course->uid}/{$search}");

        $response->assertStatus(200);
    }
    /**
     * @test Obtener roles de usuarios
     */
    public function testGetUserRoles()
    {
        // Datos: Crear roles de usuario solo si no existen
        UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid(), 'name' => 'Administrator']);
        UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid(), 'name' => 'Management']);
        UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generateUuid(), 'name' => 'Teacher']);
        UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generateUuid(), 'name' => 'Student']);

        // Act: Realizar la solicitud a la ruta
        $response = $this->get('/users/list_users/get_user_roles');

        // Assert: Verificar el estado de la respuesta
        $response->assertStatus(200);

        // Decodificar la respuesta JSON
        $userRoles = json_decode($response->getContent(), true);

        // Verificar que se devuelvan los roles en el orden correcto
        $this->assertCount(4, $userRoles);
        $this->assertEquals('ADMINISTRATOR', $userRoles[0]['code']);
        $this->assertEquals('MANAGEMENT', $userRoles[1]['code']);
        $this->assertEquals('TEACHER', $userRoles[2]['code']);
        $this->assertEquals('STUDENT', $userRoles[3]['code']);
    }

    /**
     * @test Obtener usuario
     */

    public function testGetUserSuccessfully()
    {
        // Datos: Crear un usuario de prueba
        $user = UsersModel::factory()->create()->latest()->first();

        // Realizar la solicitud a la ruta con el uid del usuario
        $response = $this->get("/users/list_users/get_user/{$user->uid}");

        // Assert: Verificar el estado de la respuesta
        $response->assertStatus(200);

        // Decodificar la respuesta JSON
        $returnedUser = json_decode($response->getContent(), true);

        // Verificar que los datos del usuario coincidan
        $this->assertEquals($user->uid, $returnedUser['uid']);
        $this->assertEquals($user->first_name, $returnedUser['first_name']);
        $this->assertEquals($user->last_name, $returnedUser['last_name']);
        $this->assertEquals($user->email, $returnedUser['email']);
        $this->assertEquals($user->nif, $returnedUser['nif']);
    }

    /**
     * @test Obtener Error 406 si usuario no existe
     */
    public function testGetUserNotFound()
    {
        // Intentar obtener un usuario que no existe
        $uid = Str::uuid();
        $response = $this->get("/users/list_users/get_user/" . $uid);

        // Verificar el estado de la respuesta
        $response->assertStatus(406);
        $response->assertJson(['message' => 'El usuario no existe']);
    }


    /**
     * @test Exportar usuarios
     */
    public function testExportUsers()
    {
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos: Crear roles de usuario
        $roleTeacher = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generateUuid(), 'name' => 'Teacher']);
        $roleStudent = UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generateUuid(), 'name' => 'Student']);


        // Datos: Crear usuarios de prueba
        $user1 = UsersModel::factory()->create()->latest()->first();
        $user1->roles()->attach($roleStudent->uid, ['uid' => generateUuid()]);

        $user2 = UsersModel::factory()->create();
        $user2->roles()->attach($roleTeacher->uid, ['uid' => generateUuid()]);

        // Realizar la solicitud a la ruta
        $response = $this->post('/users/list_users/export_users');

        //Verificar el estado de la respuesta
        $response->assertStatus(200);
        $response->assertJsonStructure(['downloadUrl']);

        // Obtener la URL de descarga del archivo
        $downloadUrl = $response->json()['downloadUrl'];

        // Extraer el nombre del archivo de la URL
        $filename = basename($downloadUrl);

        // Verificar que el archivo CSV se haya creado en la ubicación correcta
        $this->assertFileExists(public_path('downloads_temps/' . $filename));
    }

    /**
     * @test Eliminar usuarios
     */
    public function testDeleteUsers()
    {
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos: Crear roles de usuario
        $roleTeacher = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generateUuid(), 'name' => 'Teacher']);
        $roleStudent = UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generateUuid(), 'name' => 'Student']);

        // Datos: Crear usuarios de prueba
        $user1 = UsersModel::factory()->create()->latest()->first();
        $user1->roles()->attach($roleStudent->uid, ['uid' => generateUuid()]);

        $user2 = UsersModel::factory()->create();
        $user2->roles()->attach($roleTeacher->uid, ['uid' => generateUuid()]);


        // Asegúrate de que los usuarios existen antes de eliminarlos
        $this->assertDatabaseHas('users', ['uid' => $user1->uid]);
        $this->assertDatabaseHas('users', ['uid' => $user2->uid]);

        // Realizar la solicitud a la ruta para eliminar usuarios
        $response = $this->delete('/users/list_users/delete_users', [
            'usersUids' => [$user1->uid, $user2->uid]
        ]);

        // Assert: Verificar el estado de la respuesta
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se han eliminado los usuarios correctamente']);

        // Verificar que los usuarios han sido eliminados de la base de datos
        // $this->assertDatabaseMissing('users', ['uid' => $user1->uid]);
        // $this->assertDatabaseMissing('users', ['uid' => $user2->uid]);
    }

    public function testFilterUsersByCreationDate()
    {
        // Crear usuarios con diferentes fechas de creación
        UsersModel::factory()->create(['created_at' => now()->subDays(10)]);
        UsersModel::factory()->create(['created_at' => now()->subDays(5)]);
        UsersModel::factory()->create(['created_at' => now()->subDays(1)]);

        // Definir los filtros
        $filters = [
            [
                'database_field' => 'creation_date',
                'value' => [now()->subDays(7), now()->subDays(2)],
            ],
        ];

        // Realizar la solicitud
        $response = $this->json('GET', '/users/list_users/get_users', [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);

        // Comprobar que solo el usuario2 está en los resultados
        $this->assertCount(1, $response->json()['data']);
    }

    public function testFilterUsersByRoles()
    {
        // Crear roles y usuarios
        $user1 = UsersModel::factory()->create();
        $role1 = UserRolesModel::where('code', 'STUDENT')->first();

        $user1->roles()->attach($role1->uid, ['uid' => generateUuid()]);

        $user2 = UsersModel::factory()->create();
        $role2 = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user2->roles()->attach($role2->uid, ['uid' => generateUuid()]);

        // Definir los filtros
        $filters = [
            [
                'database_field' => 'roles',
                'value' => [$role1->uid], // Filtrar por rol Admin
            ],
        ];

        // Realizar la solicitud
        $response = $this->json('GET', '/users/list_users/get_users', [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);

        // Comprobar que solo el usuario1 está en los resultados
        $this->assertCount(1, $response->json()['data']);
    }

    /** @test */
    public function testValidatesRolesSelection()
    {
        // Crear una instancia del controlador que contiene el método saveUser
        $controller = new ListUsersController();

        // Crear una solicitud simulada sin roles seleccionados
        $requestData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'nif' => '12345678Z',
            'roles' => json_encode([]), // No se seleccionan roles
        ];
        // Crear una instancia de Request con los datos simulados
        $request = Request::create('/', 'POST', $requestData); // Usar Request::create

        // Usar reflexión para acceder al método privado validateUser
        $reflection = new ReflectionMethod($controller, 'validateUser');

        // Llamar al método validateUser y obtener los errores
        $validateErrors = $reflection->invoke($controller, $request);

        // Verificar que se devuelva un error relacionado con los roles
        $this->assertArrayHasKey('roles', $validateErrors->toArray());
    }

    /** @test */
    public function testReturnsAllDepartments()
    {
        /** @test */

        // Crear algunos departamentos en la base de datos
        $department1 = DepartmentsModel::factory()->create(['name' => 'HR']);
        $department2 = DepartmentsModel::factory()->create(['name' => 'IT']);

        // Crear una instancia del controlador
        $controller = new ListUsersController();

        // Llamar al método getDepartments directamente
        $response = $controller->getDepartments();


        // Verificar que la respuesta tenga un código de estado 200
        $this->assertEquals(200, $response->getStatusCode());

        // Verificar que los departamentos devueltos sean los esperados
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData); // Asegura que se devuelven 2 departamentos
        $this->assertEquals($department1->name, $responseData[0]['name']);
        $this->assertEquals($department2->name, $responseData[1]['name']);
    }

}
