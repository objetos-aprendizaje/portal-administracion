<?php

namespace Tests\Unit;


use App\Models\DepartmentsModel;
use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
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
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
         // Simular un usuario autenticado
         $user = UsersModel::factory()->create();
         $this->actingAs($user);
    }
/* @test Crea departamento*/
    public function testSaveDepartmentWithValidData()
    {
         // Simular un usuario autenticado
         $user = UsersModel::factory()->create();
         $this->actingAs($user);

        $departament = DepartmentsModel::factory()->create()->first();
        $this->assertDatabaseHas('departments', ['uid' => $departament->uid]);

        $data = [
            'uid'=> $departament->uid,
            'name' => $departament->name,
        ];

        $response = $this->postJson('/administration/departments/save_department', $data);


        // Verificar que el departamento se haya guardado en la base de datos
        $this->assertDatabaseHas('departments', [
            'uid'=> $departament->uid,
            'name' => $departament->name,
        ]);

        // Verificar que la respuesta sea correcta
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Departamento añadida correctamente', json_decode($response->getContent(), true)['message']);
    }

/* @test Crea departamento data invalidad Error*/
    public function testSaveDepartmentWithInvalidData()
    {


       $departament = DepartmentsModel::factory()->create()->first();
       $this->assertDatabaseHas('departments', ['uid' => $departament->uid]);

       $data = [
           'uid'=> $departament->uid,
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
