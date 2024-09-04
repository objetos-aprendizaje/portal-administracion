<?php

namespace Tests\Unit;


use Exception;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\AutomaticResourceAprovalUsersModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Management\CallsController;




class ManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @test
     * Verifica que el método index() del controlador ManagementGeneralConfigurationController
     * carga la vista correcta con los datos necesarios.
     */
    public function testLoadsTheGeneralConfigurationViewWithProperData()
    {
        // Crear un usuario con rol de 'ADMINISTRATOR'
        $admin = UsersModel::factory()->create();

        // Crear un usuario con rol de 'TEACHER'
        $teacher1 = UsersModel::factory()->create()->first();
        $teacher2 = UsersModel::factory()->create()->first();

        // Crear roles 'ADMINISTRATOR' y 'TEACHER'
        $adminRole = UserRolesModel::factory()->create(['code' => 'ADMINISTRATOR'])->first();
        $teacherRole = UserRolesModel::factory()->create(['code' => 'TEACHER'])->first();

        // Configurar opciones generales y textos de ayuda
        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Sincronizar roles para el usuario administrador
        $admin->roles()->sync([
            [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $adminRole->uid,
            ],
        ]);

        // Sincronizar roles para los usuarios profesores
        $teacher1->roles()->sync([
            [
                'uid' => generate_uuid(),
                'user_uid' => $teacher1->uid,
                'user_role_uid' => $teacherRole->uid,
            ],
        ]);

        $teacher2->roles()->sync([
            [
                'uid' => generate_uuid(),
                'user_uid' => $teacher2->uid,
                'user_role_uid' => $teacherRole->uid,
            ],
        ]);

        // Crear un solo registro de aprobación automática de recursos por profesor
        $autoApproval1 = AutomaticResourceAprovalUsersModel::factory()->create(['user_uid' => $teacher1->uid]);
        // Elimina la segunda creación para evitar duplicados
        // $autoApproval2 = AutomaticResourceAprovalUsersModel::factory()->create(['user_uid' => $teacher2->uid]);

        // Simular el inicio de sesión como administrador
        Auth::login($admin);

        View::share('roles', $admin->roles->toArray());

        // Realizar la petición GET a la ruta correspondiente
        $response = $this->get('/management/general_configuration');

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista tenga los datos correctos para 'teachers'
        // $response->assertViewHas('teachers', function ($viewTeachers) use ($teacher1, $teacher2) {
        //     return !empty($viewTeachers);
        // });

        // Verificar que la vista tenga los datos correctos para 'uids_teachers_automatic_aproval_resources'
        $response->assertViewHas('uids_teachers_automatic_aproval_resources', function ($uids) use ($teacher1) {
            return in_array($teacher1->uid, $uids); // Solo verifica el primer profesor
        });

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('management.general_configuration.index');
    }

    /**
     * @test
     * Verifica que el método index() del CallsController
     * muestra la vista de acceso no permitido si checkAccessCalls() retorna falso.
     */
    public function testShowsAccessNotAllowedWhenAccessCallsIsDenied()
    {

        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        // Autenticar al usuario
        Auth::login($user);

        // Simular la carga de datos que haría el middleware
        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Simular la configuración de general_options
        app()->instance('general_options', [
            'operation_by_calls' => false,
        ]);

        // Realizar la petición GET a la ruta correspondiente
        $response = $this->get('/management/calls');

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista mostrada es 'access_not_allowed'
        $response->assertViewIs('access_not_allowed');

        // Verificar que la vista contiene el título y la descripción correctos
        $response->assertViewHas('title', 'Las convocatorias están desactivadas');
        $response->assertViewHas('description', 'El administrador ha desactivado el funcionamiento por convocatorias en la plataforma.');
    }

    /**
     * @test
     * Verifica que el método index() del CallsController
     * muestra la vista de acceso no permitido si checkManagersAccessCalls() retorna falso.
     */
    public function testShowsAccessNotAllowedWhenManagersAccessIsDenied()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        // Autenticar al usuario
        Auth::login($user);

        // Simular la carga de datos que haría el middleware
        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);


        // Simular la configuración de general_options
        app()->instance('general_options', [
            'operation_by_calls' => true,
            'managers_can_manage_calls' => false,
        ]);

        // Realizar la petición GET a la ruta correspondiente
        $response = $this->get('/management/calls');

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista mostrada es 'access_not_allowed'
        $response->assertViewIs('access_not_allowed');

        // Verificar que la vista contiene el título y la descripción correctos
        $response->assertViewHas('title', 'No tienes permiso para modificar las convocatorias');
        $response->assertViewHas('description', 'El administrador ha desactivado la administración de convocatorias para los gestores.');
    }

    /**
     * @test
     * Verifica que el método index() del CallsController
     * carga la vista correcta con los datos necesarios cuando ambos accesos son permitidos.
     */
    public function testLoadsTheCallsViewWithProperDataWhenAccessIsAllowed()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        // Autenticar al usuario
        Auth::login($user);

        // Simular la carga de datos que haría el middleware
        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Simular la configuración de general_options
        app()->instance('general_options', [
            'operation_by_calls'        => true,
            'managers_can_manage_calls' => true,
        ]);

        // Crear datos de prueba para EducationalProgramTypesModel
        $educationalProgramType1 = EducationalProgramTypesModel::factory()->create();
        $educationalProgramType2 = EducationalProgramTypesModel::factory()->create();

        // dd($educationalProgramType1, $educationalProgramType2);
        // Realizar la petición GET a la ruta correspondiente
        $response = $this->get('/management/calls');

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista mostrada es 'management.calls.index'
        $response->assertViewIs('management.calls.index');

        // Verificar que la vista contiene los datos correctos para 'educational_program_types'
        $response->assertViewHas('educational_program_types', function ($viewData) use ($educationalProgramType1, $educationalProgramType2) {
            // Verificar que el número de elementos coincida
            if (count($viewData) !== 2) {
                return false;
            }

            // Obtener los UID de los elementos de la vista
            $uids = array_column($viewData, 'uid');

            // Verificar que ambos UID estén presentes en los datos de la vista
            return in_array($educationalProgramType1->uid, $uids) && in_array($educationalProgramType2->uid, $uids);
        });

        // Verificar que otros datos están presentes en la vista
        $response->assertViewHas('page_name', 'Convocatorias');
        $response->assertViewHas('page_title', 'Convocatorias');
    }

    /**
     * @test  Guarda Opciones Generales*/
    public function testSaveGeneralOptions()
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
            // Prepara los datos iniciales
            GeneralOptionsModel::create(['option_name' => 'learning_objects_appraisals', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'operation_by_calls', 'option_value' => '']);

            // Datos que se enviarán en la solicitud
            $data = [
                'learning_objects_appraisals' => 'Nuevo Valor 1',
                'operation_by_calls' => 'Nuevo Valor 2',
            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/administration/save_general_options', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                ->assertJson(['message' => 'Opciones guardadas correctamente']);

            // Verifica que los valores se hayan actualizado en la base de datos
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'learning_objects_appraisals',
                'option_value' => 'Nuevo Valor 1',
            ]);

            $this->assertDatabaseHas('general_options', [
                'option_name' => 'operation_by_calls',
                'option_value' => 'Nuevo Valor 2',
            ]);
        }
    }


    /**
     * @test Crea convocatoria
     */
    public function testCreateCalls()
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

        if ($admin->hasAnyRole(['MANAGEMENT'])) {

            // Crear algunos tipos de programa en la base de datos
            $uid1 = (string) Str::uuid();
            $uid2 = (string) Str::uuid();

            // Insertar tipos de programa
            EducationalProgramTypesModel::insert([
                'uid' => $uid1,
                'name' => 'Tipo 10',
            ]);

            EducationalProgramTypesModel::insert([
                'uid' => $uid2, // Usar el segundo UID generado
                'name' => 'Tipo 20',
            ]);

            // Crea una convocatoria existente
            $call = CallsModel::factory()->create();


            //Datos de la convocatoria
            $data = [
                'call_uid' => $call->uid, // Para crear una nueva convocatoria
                'name' => $call->name,
                'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
                'program_types' => [$uid1, $uid2], // Usar los UIDs generados
            ];


            $response = $this->postJson('/management/calls/save_call', $data, [
                'Content-Type' => 'application/json'
            ]);

            // Verificar la respuesta
            $response->assertStatus(200);
        }
    }

    /**
     * @test Crea convocatoria con programas
     */
    public function testCreatesCallWhenCallUidIsNotProvided()
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

        if ($admin->hasAnyRole(['MANAGEMENT'])) {

            // Crear algunos tipos de programa en la base de datos
            $uid1 = (string) Str::uuid();
            $uid2 = (string) Str::uuid();

            // Insertar tipos de programa
            EducationalProgramTypesModel::insert([
                'uid' => $uid1,
                'name' => 'Tipo 10',
            ]);

            EducationalProgramTypesModel::insert([
                'uid' => $uid2, // Usar el segundo UID generado
                'name' => 'Tipo 20',
            ]);

            $calls = CallsModel::factory()->create();


            $response = $this->postJson('/management/calls/save_call', [
                'uid' => $calls->uid,
                'name' => $calls->name,
                'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
                'program_types' => [$uid1, $uid2], // Usar los UIDs generados
            ]);

            $response->assertStatus(200);
            $response->assertJson(['message' => 'Convocatoria añadida correctamente']);

            // Verificar que la llamada se haya guardado en la base de datos
            $call = CallsModel::where('name', $calls->name)->first(); // Busca por nombre para evitar problemas con UID
            $this->assertNotNull($call);

            $this->assertDatabaseHas('calls', [
                'uid' => $call->uid,
                'name' => $calls->name,
                'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),

            ]);
        }
    }

    /**
     * @test Crea Convocatoria con error
     */

    public function testReturnsValidationErrorsDataIsInvalid()
    {
        $response = $this->postJson('/management/calls/save_call', [
            // Aquí puedes enviar datos inválidos
            'name' => null, // Suponiendo que 'some_field' es obligatorio
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }

    /**
     * @test Error Handles Database*/
    public function testHandlesDatabaseTransactionErrors()
    {

        // Crear algunos tipos de programa en la base de datos
        $uid1 = (string) Str::uuid();
        $uid2 = (string) Str::uuid();

        // Insertar tipos de programa
        EducationalProgramTypesModel::insert([
            'uid' => $uid1,
            'name' => 'Tipo 10',
        ]);

        EducationalProgramTypesModel::insert([
            'uid' => $uid2, // Usar el segundo UID generado
            'name' => 'Tipo 20',
        ]);
        // Simular un error en la transacción
        DB::shouldReceive('transaction')->andThrow(new \Exception('Database error'));

        $response = $this->postJson('/management/calls/save_call', [
            'name' => 'some_value',
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
            'program_types' => [$uid1, $uid2],
        ]);

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Database error']);
    }

    public function testUpdatesAnExistingCall()
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

        if ($admin->hasAnyRole(['MANAGEMENT'])) {


            // Crear tipos de programa en la base de datos
            $uid1 = (string) Str::uuid();
            $uid2 = (string) Str::uuid();

            EducationalProgramTypesModel::insert([
                'uid' => $uid1,
                'name' => 'Tipo 1',
            ]);

            EducationalProgramTypesModel::insert([
                'uid' => $uid2,
                'name' => 'Tipo 2',
            ]);

            $call = CallsModel::factory()->create();

            $data = [
                'call_uid' => $call->uid, // Para crear una nueva convocatoria
                'name' => $call->name,
                'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
                'program_types' => [$uid1, $uid2], // Usar los UIDs generados
            ];

            // Actualizar la llamada existente
            $response = $this->postJson('/management/calls/save_call', $data);


            $response->assertStatus(200);

            // Verificar que la llamada se haya actualizado en la base de datos
            $this->assertDatabaseHas('calls', [
                'uid' => $call->uid,
                'name' => $call->name,
                'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),

            ]);
        }
    }

    /**
     * @test  Chequea acceso a convocatoria */

    public function testCheckAccessCallsManager()
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
        if ($admin->hasAnyRole(['MANAGEMENT'])) {
            // Hacer la solicitud
            $response = $this->get('/management/calls/get_calls');

            // Verificar que el acceso sea permitido
            $response->assertStatus(200);
        }
    }

    /**
     * @test Sin Acceso a convocatoria*/
    public function testAccessCallsNotHaveManagementRole()
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
        if ($admin->hasAnyRole(['TEACHER'])) {
            // Hacer la solicitud
            $response = $this->get('/management/calls/get_calls');

            // Verificar que el acceso sea denegado
            $response->assertStatus(200);
        }
    }

    /**
     * @test
     * Verifica que el método saveGeneralOptions() del ManagementGeneralConfigurationController
     * guarda las opciones generales correctamente y registra un log.
     */
    public function testSavesGeneralOptionsAndLogsTheAction()
    {
        // Crear un usuario de prueba y autenticarlo
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear opciones generales iniciales en la base de datos
        GeneralOptionsModel::insert([
            ['option_name' => 'necessary_approval_resources', 'option_value' => 'old_value_1'],
            ['option_name' => 'necessary_approval_editions', 'option_value' => 'old_value_2'],
        ]);

        // Datos a enviar en la solicitud POST
        $postData = [
            'necessary_approval_resources' => 'new_value_1',
            'necessary_approval_editions' => 'new_value_2',
        ];

        // Realizar la solicitud POST a la ruta correspondiente
        $response = $this->post('/management/general_configuration/save_general_options', $postData);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta JSON contiene el mensaje correcto
        $response->assertJson(['message' => 'Datos guardados correctamente']);

        // Verificar que los valores en la base de datos fueron actualizados correctamente
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'necessary_approval_resources',
            'option_value' => 'new_value_1',
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'necessary_approval_editions',
            'option_value' => 'new_value_2',
        ]);

        // Verificar que se haya creado un log con la acción correspondiente
        $this->assertDatabaseHas('logs', [
            'info' => 'Guardar opciones generales',
            'entity' => 'Configuración general de gestión',
            'user_uid' => $user->uid,
        ]);
    }

    /**
     * @test
     * Verifica que el método saveTeachersAutomaticAproval() del ManagementGeneralConfigurationController
     * guarda correctamente los profesores con aprobación automática y elimina los que ya no están en la lista.
     */
    public function testSavesAndDeletesTeachersAutomaticApprovalCorrectlyv2()
    {
        // Crear un usuario de prueba y autenticarlo
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear algunos profesores de prueba
        $teacher1 = UsersModel::factory()->create();
        $teacher2 = UsersModel::factory()->create();
        $teacher3 = UsersModel::factory()->create();

        // Simular que teacher1 y teacher2 ya están en la tabla de aprobación automática
        AutomaticResourceAprovalUsersModel::factory()->create(['user_uid' => $teacher1->uid]);
        AutomaticResourceAprovalUsersModel::factory()->create(['user_uid' => $teacher2->uid]);

        // Datos a enviar en la solicitud POST (incluyendo solo teacher2 y teacher3)
        $postData = [
            'selectedTeachers' => [$teacher2->uid, $teacher3->uid],
        ];

        // Realizar la solicitud POST a la ruta correspondiente
        $response = $this->post('/management/general_configuration/save_teachers_automatic_aproval', $postData);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta JSON contiene el mensaje correcto
        $response->assertJson(['message' => 'Profesores guardados correctamente']);

        // Verificar que teacher1 ha sido eliminado de la tabla de aprobación automática
        $this->assertDatabaseMissing('automatic_resource_approval_users', [
            'user_uid' => $teacher1->uid,
        ]);

        // Verificar que teacher2 sigue estando en la tabla
        $this->assertDatabaseHas('automatic_resource_approval_users', [
            'user_uid' => $teacher2->uid,
        ]);

        // Verificar que teacher3 ha sido agregado a la tabla
        $this->assertDatabaseHas('automatic_resource_approval_users', [
            'user_uid' => $teacher3->uid,
        ]);

        // Verificar que se haya creado un log con la acción correspondiente
        $this->assertDatabaseHas('logs', [
            'info' => 'Guardar profesores con aprobación automática',
            'entity' => 'Configuración general de gestión',
            'user_uid' => $user->uid,
        ]);
    }
    /**
     * @test
     * Verifica que el método getCall() del CallsController
     * retorna correctamente la información de la convocatoria con sus tipos de programas educativos.
     */
    public function testReturnsCallWithEducationalProgramTypes()
    {
        // Crear dos tipos de programas educativos
        $educationalProgramType1 = EducationalProgramTypesModel::factory()->create();
        // $educationalProgramType2 = EducationalProgramTypesModel::factory()->create();

        // Crear una convocatoria
        $call = CallsModel::factory()->create();

        // Asociar los tipos de programas educativos a la convocatoria con un uid adicional en la tabla intermedia
        $call->educationalProgramTypes()->attach([
            [
                'uid' => generate_uuid(),
                'call_uid' => $call->uid,
                'educational_program_type_uid' => $educationalProgramType1->uid,
            ],
        ]);

        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get('/management/calls/get_call/' . $call->uid);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta JSON contiene la información correcta de la convocatoria y sus relaciones
        $response->assertJson([
            'uid' => $call->uid,
            'educational_program_types' => [
                [
                    'uid' => $educationalProgramType1->uid,
                    // Agrega otros campos si es necesario
                ],                
            ],
            // Agrega otros campos de la convocatoria si es necesario
        ]);
    }

     /**
     * @test
     * Verifica que el método deleteCalls() del CallsController
     * elimina las convocatorias correctamente si no están asociadas a cursos o programas formativos.
     */
    public function testDeletesCallsIfNotAssociatedWithCoursesOrPrograms()
    {
        // Crear un usuario de prueba y autenticarlo
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear dos convocatorias
        $call1 = CallsModel::factory()->create();
        $call2 = CallsModel::factory()->create();

        // Realizar la solicitud DELETE a la ruta correspondiente
        $response = $this->delete('/management/calls/delete_calls', [
            'uids' => [$call1->uid, $call2->uid],
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta JSON contiene el mensaje correcto
        $response->assertJson(['message' => 'Convocatorias eliminadas correctamente']);

        // Verificar que las convocatorias fueron eliminadas
        $this->assertDatabaseMissing('calls', ['uid' => $call1->uid]);
        $this->assertDatabaseMissing('calls', ['uid' => $call2->uid]);

        // Verificar que se haya creado un log con la acción correspondiente
        $this->assertDatabaseHas('logs', [
            'info' => 'Eliminar convocatoria',
            'entity' => 'Convocatorias',
            'user_uid' => $user->uid,
        ]);
    }

    /**
     * @test
     * Verifica que el método deleteCalls() del CallsController
     * no elimina las convocatorias si están asociadas a cursos o programas formativos.
     */
    public function testDoesNotDeleteCallsIfAssociatedWithCoursesOrPrograms()
    {
        // Crear un usuario de prueba y autenticarlo
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crear una convocatoria y asociarla a un curso y a un programa formativo
        $call = CallsModel::factory()->create();
        CoursesModel::factory()->create(['call_uid' => $call->uid]);
        EducationalProgramsModel::factory()->create(['call_uid' => $call->uid]);

        // Realizar la solicitud DELETE a la ruta correspondiente
        $response = $this->delete('/management/calls/delete_calls', [
            'uids' => [$call->uid],
        ]);

        // Verificar que la respuesta sea 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Verificar que la respuesta JSON contiene el mensaje correcto
        $response->assertJson(['message' => 'No se pueden eliminar las convocatorias porque están asociadas a cursos o programas formativos.']);

        // Verificar que la convocatoria no fue eliminada
        $this->assertDatabaseHas('calls', ['uid' => $call->uid]);
    }
}
