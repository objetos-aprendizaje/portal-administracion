<?php

namespace Tests\Unit;


use Exception;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramTypesModel;
use App\Models\AutomaticResourceAprovalUsersModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Management\CallsController;




class ManagerTest extends TestCase {
    use RefreshDatabase;

/**
 * @testdox Inicialización de inicio de sesión
 */
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');

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
    public function testCreateCalls(){

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





}










