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
