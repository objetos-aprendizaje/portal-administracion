<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\ApiKeysModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiRegisterControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test  Verifica que los usuarios se registran correctamente.
     */
    public function testRegistersUsersSuccessfully()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Generar un correo electrónico aleatorio
        $email = Str::random(10) . '@example.com';
        // Datos de prueba para un usuario
        $user = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nif' => '13249954E', // Debe ser un NIF válido según la regla NifNie
            'curriculum' =>'',
            'email' => $email,
            'roles' => ['TEACHER'],
            'password' => 'password123',
        ];

        // Realizar la solicitud POST con los datos de usuario
        $response = $this->postJson('/api/register_user', [$user], [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Usuarios registrados correctamente']);

        // Verificar que el usuario fue guardado en la base de datos
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * @test  Verifica que las validaciones fallan cuando los datos son incorrectos.
     */
    public function testValidationFailsWithInvalidData()
    {

         // Crea un usuario y actúa como él
         $admin = UsersModel::factory()->create();
         $this->actingAs($admin);
 
         // Datos de para genera la key de la api 
         $apikey = ApiKeysModel::factory()->create()->first();
 
         $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Datos de prueba con un campo obligatorio faltante (por ejemplo, 'email' faltante)
        $user = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'nif' => '12345678A', // Debe ser un NIF válido según la regla NifNie
            'roles' => ['STUDENT'],
            // 'email' => 'janedoe@example.com', // Faltante a propósito
        ];

        // Realizar la solicitud POST con los datos de usuario
        $response = $this->postJson('/api/register_user', [$user],[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene el mensaje de error esperado
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * @test  Verifica que las validaciones fallan cuando el NIF es inválido.
     */
    public function testValidationFailsWithInvalidNif()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Datos de prueba con un NIF inválido
        $user = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'nif' => 'INVALID_NIF', // NIF inválido
            'email' => 'janedoe@example.com',
            'roles' => ['STUDENT'],
        ];

        // Realizar la solicitud POST con los datos de usuario
        $response = $this->postJson('/api/register_user', [$user],[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene el mensaje de error esperado
        $response->assertJsonValidationErrors(['nif']);
    }
}
