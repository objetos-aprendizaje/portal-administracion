<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\DepartmentsModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Http\Controllers\Administration\DepartmentsController;



class AdministrationDepartamentsTest extends TestCase
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
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
    }


    /** @test Obtener Index View Departamentos */
    public function testIndexViewTheDepartaments()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]); // Crea roles de prueba
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
        // Realiza una solicitud GET a la ruta especificada
        $response = $this->get(route('departments'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se cargue la vista correcta
        $response->assertViewIs('administration.departments.index');

        // Verifica que los datos pasados a la vista sean correctos
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('page_name', 'Departamentos');
        $response->assertViewHas('page_title', 'Departamentos');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/departments.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'departments');
    }

    /** @test Crea departamento*/
    public function testSaveDepartmentWithValidData()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $departament = DepartmentsModel::factory()->create()->first();
        $this->assertDatabaseHas('departments', ['uid' => $departament->uid]);

        $data = [
            'uid' => $departament->uid,
            'name' => $departament->name,
        ];

        $response = $this->postJson('/administration/departments/save_department', $data);


        // Verificar que el departamento se haya guardado en la base de datos
        $this->assertDatabaseHas('departments', [
            'uid' => $departament->uid,
            'name' => $departament->name,
        ]);

        // Verificar que la respuesta sea correcta
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Departamento añadida correctamente', json_decode($response->getContent(), true)['message']);
    }

    /** @test Actualiza un departamento*/
    public function testUpdateDepartmentWithValidData()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $departament = DepartmentsModel::factory()->create()->first();       

        $data = [
            'department_uid'=>  $departament->uid,            
            'name' => $departament->name,
        ];

        $response = $this->postJson('/administration/departments/save_department', $data);

        // Verificar que el departamento se haya guardado en la base de datos
        $this->assertDatabaseHas('departments', [
            'uid' => $departament->uid,
            'name' => $departament->name,
        ]);

        // Verificar que la respuesta sea correcta
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Departamento actualizada correctamente', json_decode($response->getContent(), true)['message']);
    }

    /* @test Crea departamento data invalidad Error*/
    public function testSaveDepartmentWithInvalidData()
    {

        $departament = DepartmentsModel::factory()->create()->first();
        $this->assertDatabaseHas('departments', ['uid' => $departament->uid]);

        $data = [
            'uid' => $departament->uid,
            'name' => '',
        ];

        $response = $this->postJson('/administration/departments/save_department', $data);


        // Verificar que la respuesta sea un error 422
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Algunos campos son incorrectos', json_decode($response->getContent(), true)['message']);
        $this->assertArrayHasKey('name', json_decode($response->getContent(), true)['errors']);
    }
    /**
     * @test Obtener listado departamento*/
    public function testGetDepartmentReturnsCorrectData()
    {
        // Crear un departamento en la base de datos
        $department = DepartmentsModel::factory()->create()->first();
        $this->assertDatabaseHas('departments', ['uid' => $department->uid]);

        // Hacer la solicitud GET a la ruta
        $response = $this->get('/administration/departments/get_department/' . $department->uid);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson([
            'uid' => $department->uid,
            'name' => $department->name,
        ]);
    }
    
    /**
     * @test Elimina departamento*/
    public function testDeleteDepartmentsWithoutUsersReturnsSuccess()
    {
        // Crear departamentos de prueba
        $department1 = DepartmentsModel::factory()->create();
        $department2 = DepartmentsModel::factory()->create();

        // Hacer la solicitud DELETE a la ruta
        $response = $this->delete('/administration/departments/delete_departments', [
            'uids' => [$department1->uid, $department2->uid],
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Departamentos eliminados correctamente']);

        // Verificar que los departamentos se hayan eliminado de la base de datos
        $this->assertDatabaseMissing('departments', ['uid' => $department1->uid]);
        $this->assertDatabaseMissing('departments', ['uid' => $department2->uid]);
    }

     /**
     * @test Elimina departamento*/
    public function testDeleteDepartmentsWithoutUsersReturnsException()
    {
        // Crear departamentos de prueba
        $department1 = DepartmentsModel::factory()->create();
        
        UsersModel::factory()->create([
            'department_uid'=> $department1->uid,
        ]);        

        // Hacer la solicitud DELETE a la ruta
        $response = $this->delete('/administration/departments/delete_departments', [
            'uids' => [$department1->uid],
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(406);
        $response->assertJson(['message' => 'No se pueden eliminar los departamentos porque hay usuarios vinculados a ellos']);

      
    }

    /** @test Obtener todos los departamentos */
    public function testGetDepartments()
    {
        // Crea algunos departamentos para la prueba
        DepartmentsModel::factory()->create(['name' => 'Finance']);
        DepartmentsModel::factory()->create(['name' => 'Human Resources']);
        DepartmentsModel::factory()->create(['name' => 'IT']);

        // Define los parámetros de búsqueda y ordenamiento
        $params = [
            'size' => 10,
            'search' => 'Finance',
            'sort' => [
                ['field' => 'name', 'dir' => 'asc'],
            ],
        ];

        // Realiza una solicitud GET a la ruta
        $response = $this->getJson('/administration/departments/get_departments?' . http_build_query($params));

        // Verifica que la respuesta tenga un código de estado 200
        $response->assertStatus(200);

        // Verifica que la respuesta sea un JSON
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uid',
                    'name',
                ],
            ],
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);

        // Verifica que el resultado contenga el departamento buscado
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Finance', $response->json('data.0.name'));
    }


}
