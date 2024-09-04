<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use App\Models\UserRolesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiUpdateUserControllerTest extends TestCase
{

    use RefreshDatabase;
    /**
     * @test  Verifica que un usuario se actualiza correctamente.
     */
    public function testUserIsUpdatedSuccessfully()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);
        // Crear un usuario existente en la base de datos
        $user = UsersModel::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'nif' => '29763362M',
            'email' => 'janedoe@example.com',
        ]);

        // Crear un rol existente en la base de datos
        $role = UserRolesModel::factory()->create([
            'name' => 'Docente',
            'code' => 'TEACHER'
        ])->latest()->first();

        // Datos de la solicitud para actualizar el usuario
        $updateData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nif' => '29763362M',
            'curriculum' => 'New curriculum description',
            'roles' => ['TEACHER'],
            'password' => 'newpassword123',
            'new_email' => 'johndoe@example.com',
        ];

        // Realizar la solicitud POST con los datos de actualización del usuario
        $response = $this->postJson('/api/update_user/' . $user->email, $updateData, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Usuario actualizado correctamente']);

        // Verificar que el usuario fue actualizado en la base de datos
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nif' => '29763362M',
            'email' => 'johndoe@example.com',
            'curriculum' => 'New curriculum description',
        ]);

        // Verificar que el rol del usuario se haya actualizado correctamente
        $this->assertDatabaseHas('user_role_relationships', [
            'user_uid' => $user->uid,
            'user_role_uid' => $role->uid,
        ]);
    }

    /**
     * @test  Verifica que la validación falla cuando se proporcionan datos incorrectos.
     */
    public function testValidationFailsWithInvalidDataApiUpdateUser()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Crear un usuario existente en la base de datos
        $user = UsersModel::factory()->create([
            'email' => 'janedoe@example.com',
        ]);

        // Datos de la solicitud con campos incorrectos
        $updateData = [
            'first_name' => '', // Nombre vacío
            'last_name' => '',  // Apellidos vacíos
            'nif' => 'invalid-nif', // NIF inválido
            'new_email' => 'not-an-email', // Correo electrónico no válido
        ];

        // Realizar la solicitud POST con datos incorrectos
        $response = $this->postJson('/api/update_user/' . $user->email, $updateData,[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene los mensajes de error esperados
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'nif', 'new_email']);
    }

    /**
     * @test  Verifica que se maneja correctamente cuando el usuario no es encontrado.
     */
    public function testUserNotFoundApiUpdateUser()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Datos de la solicitud para actualizar el usuario
        $updateData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nif' => '29763362M',
            'curriculum' => 'New curriculum description',
            'roles' => ['TEACHER'],
            'password' => 'newpassword123',
            'new_email' => 'johndoe@example.com',
        ];

        // Realizar la solicitud POST con un email que no existe
        $response = $this->postJson('/api/update_user/nonexistentuser@example.com', $updateData,[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 404 (Not Found)
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Usuario no encontrado']);
    }
}
