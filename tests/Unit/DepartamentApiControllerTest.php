<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\ApiKeysModel;
use App\Models\CoursesModel;
use App\Models\DepartmentsModel;
use App\Models\GeneralOptionsModel;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\View;
use App\Models\EducationalProgramsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartamentApiControllerTest extends TestCase
{
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp();
        $this->apikey = ApiKeysModel::factory()->create([
            'api_key' => 'XH1e8I8ulIDHAc6vy4gJ3lP'
        ])->first();

    }


/**
 * @test Agregar departamento
 */

    public function testCanAddDepartmentsSuccessfully()
    {


          // Usar el factory para crear departamentos
          $departments = [
            ['name' => 'HR'],
            ['name' => 'Engineering'],
        ];

         // Simular una solicitud con una cabecera API-KEY válida
         $response = $this->postJson('/api/departments/add', $departments,  ['API-KEY' => $this->apikey->api_key]);

         // Verificar que se permite el acceso y se devuelve el mensaje esperado
        $response->assertStatus(200)
        ->assertJson(['message' => 'Departamentos añadidos correctamente']);


    }

/** @test ErrorValidationName*/

      public function testErrorValidationDepartment()
      {

          // Crear un departamento con nombre nulo
          $departments = [
              ['name' => null],
          ];

          $response = $this->postJson('/api/departments/add', $departments,['API-KEY' => $this->apikey->api_key]);

          $response->assertStatus(400)
                   ->assertJson(['errors' => ['name' => ['El campo NAME es obligatorio']]]);
      }

      /** @test */
    public function testError400NameMissing()
    {

        // Crear un departamento sin nombre para provocar un error
        $departments = [
            ['name' => 'HR'],
            ['name' => ''], // Nombre vacío
        ];

        $response = $this->postJson('/api/departments/add', $departments,['API-KEY' => $this->apikey->api_key]);


        $response->assertStatus(400)
                 ->assertJsonStructure(['errors' => ['name']]);
    }


    /** @test  Mostrar departamentos*/
    public function testGetDepartmentsSuccessfully()
    {

         DepartmentsModel::factory()->count(3)->create();
        // Realizar una solicitud GET al endpoint
        $response = $this->getJson('/api/departments/get',['API-KEY' => $this->apikey->api_key]);

        // Verificar que se permite el acceso y se devuelve el estado correcto
        $response->assertStatus(200);

        // Verificar que la respuesta contiene los departamentos esperados
        $departments = DepartmentsModel::all();
        $response->assertJson($departments->toArray());
    }

     /** @test */
     public function testUpdateDepartmentSuccessfully()
     {


        $department = DepartmentsModel::factory()->create([
            'name' => 'Old Department Name',
        ]);
         // Datos para actualizar el departamento
         $data = [
             'name' => 'New Department Name',
         ];

         // Realizar una solicitud PUT al endpoint con el uid del departamento
         $response = $this->putJson('/api/departments/update/' . $department->uid, $data, ['API-KEY' => $this->apikey->api_key]);

         // Verificar que se permite el acceso y se devuelve el mensaje esperado
         $response->assertStatus(200)
                  ->assertJson(['message' => 'Departamento actualizado correctamente']);

         /// Verificar que el departamento se haya actualizado en la base de datos
        $department->refresh(); // Refrescar el modelo para obtener los últimos datos
        $this->assertEquals('New Department Name', $department->name);
    }

    /** @test */
    public function testErrorDepartmentNotFound()
    {

        $uid = generate_uuid();
        DepartmentsModel::where('uid', $uid)->first();
        // Datos para actualizar un departamento que no existe
        $data = [
            'name' => 'Updated Department Name',
        ];

        // Realizar una solicitud PUT al endpoint con un uid que no existe
        $response = $this->putJson('/api/departments/update/'.$uid, $data, ['API-KEY' => $this->apikey->api_key]);

        // Verificar que se devuelve un error 404
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Departamento no encontrado']);
    }

/** @test Borra departamento*/
     public function testDeleteDepartmentSuccessfully()
     {
         // Crear un departamento en la base de datos para esta prueba
         $department = DepartmentsModel::factory()->create([
             'name' => 'Department to be deleted',
         ])->first();

         $data=[];

         // Realizar una solicitud DELETE al endpoint con el uid del departamento
         $response = $this->deleteJson('/api/departments/delete/' . $department->uid,$data,['API-KEY' => $this->apikey->api_key]);

         // Verificar que se permite el acceso y se devuelve el mensaje esperado
         $response->assertStatus(200)
                  ->assertJson(['message' => 'Departamento eliminado correctamente']);
     }

/** @test  Departamento no encontrado al eliminar*/
    public function testError404DepartmentNotFoundNotDelete()
    {
        $uid_delete = generate_uuid();
        $data = [
            'name' => 'Updated Department Name',
        ];
        // Intentar eliminar un departamento que no existe
        $response = $this->deleteJson('/api/departments/delete/'. $uid_delete, $data, ['API-KEY' => $this->apikey->api_key]);

        // Verificar que se devuelve un error 404
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Departamento no encontrado']);
    }

     /** @test */
     public function testErrorDepartmentIsLinkedToUsers()
     {

         $department = DepartmentsModel::factory()->create([
             'name' => 'Department with users',
         ])->first();

         // Crear un usuario vinculado al departamento
         UsersModel::factory()->create([
             'department_uid' => $department->uid,
         ])->first();

         $data = [
            'name' => 'Department Name',
        ];

         // Intentar eliminar el departamento vinculado a un usuario
         $response = $this->deleteJson('/api/departments/delete/' . $department->uid, $data, ['API-KEY' => $this->apikey->api_key]);

         // Verificar que se lanza una excepción y se devuelve el mensaje correspondiente
         $response->assertJson(['message' => 'No se puede eliminar el departamento porque está vinculado a usuarios']);
     }





}
