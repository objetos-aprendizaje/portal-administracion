<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\ApiKeysModel;
use App\Models\UserRolesModel;
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
            'curriculum' => '',
            'email' => $email,
            'roles' => ['TEACHER'],
            'password' => 'password123',
        ];

        // Realizar la solicitud POST con los datos de usuario
        $response = $this->postJson('/api/register_users', [$user], [
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
     * @test
     * Verifica que no se registran usuarios si no se especifican.
     */
    public function testFailsIfNoUsersProvided()
    {
        // Crea un usuario administrador y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos para generar la key de la API
        $apikey = ApiKeysModel::factory()->create()->first();

        // Enviar la solicitud sin ningún usuario
        $response = $this->postJson('/api/register_users', [], [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta tenga un estado 400 (error)
        $response->assertStatus(400);

        // Verificar que el mensaje de error sea el esperado
        $response->assertJson([
            'errors' => ['users' => 'Debe especificar al menos un usuario'],
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
        $response = $this->postJson('/api/register_users', [$user], [
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
        $response = $this->postJson('/api/register_users', [$user], [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene el mensaje de error esperado
        $response->assertJsonValidationErrors(['nif']);
    }


    /**
     * @test
     * Verifica que la API devuelve correctamente los roles de usuario.
     */
    public function testGetRolesReturnsUserRoles()
    {

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        // Crear algunos roles de usuario simulados
        $roles = UserRolesModel::get();

        // Hacer la solicitud GET a la ruta que devuelve los roles
        $response = $this->getJson('/api/users/get_roles', [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que la cantidad de roles devueltos es la correcta
        $response->assertJsonCount(4);
    }
    /**
     * @test
     * Prueba que el método getUsers devuelve los usuarios filtrados correctamente.
     */
    public function testGetUsersReturnsFilteredUsers()
    {
        // Crear roles simulados
        $roleTeacher = UserRolesModel::factory()->create(['code' => 'TEACHER']);
        $roleStudent = UserRolesModel::factory()->create(['code' => 'STUDENT']);

        // Crear usuarios simulados
        $user1 = UsersModel::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);
        $user2 = UsersModel::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        // Asignar roles a los usuarios
        $user1->roles()->attach($roleTeacher->uid, ['uid' => generate_uuid()]);
        $user2->roles()->attach($roleStudent->uid, ['uid' => generate_uuid()]);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();
        // Realizar la solicitud GET con filtros (por nombre y rol)
        $response = $this->getJson('/api/users?first_name=John&roles=TEACHER', [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que se devuelven los usuarios filtrados correctamente
        $responseData = $response->json();
        $this->assertCount(1, $responseData);
        $this->assertEquals('John', $responseData[0]['first_name']);
        $this->assertEquals('Doe', $responseData[0]['last_name']);
        $this->assertEquals('john.doe@example.com', $responseData[0]['email']);
        $this->assertEquals('TEACHER', $responseData[0]['roles'][0]['code']);
    }

    /**
     * @test
     * Prueba que un usuario se actualiza correctamente a través de la API.
     */
    //Todo: pendiente por terminar faltando un metodo privaddo
    // public function testUpdateUserSuccessfully()
    // {
    //     // Crear un usuario existente en la base de datos
    //     $user = UsersModel::factory()->create([
    //         'email' => 'existing@example.com',
    //         'first_name' => 'John',
    //         'last_name' => 'Doe',
    //         'nif' => '12345678A',
    //         'curriculum' => 'Antiguo curriculum',
    //         'password' => bcrypt('oldpassword'),
    //     ]);

    //     // Crear una clave API válida para autenticar la solicitud
    //     $apikey = ApiKeysModel::factory()->create()->first();

    //     // Datos que se utilizarán para la actualización
    //     $updateData = [
    //         'first_name' => 'Jane',
    //         'last_name' => 'Smith',
    //         'nif' => '87654321B',
    //         'curriculum' => 'Nuevo curriculum',
    //         'new_email' => 'newemail@example.com',
    //         'password' => 'newpassword123',
    //         'roles' => ['TEACHER'],
    //     ];

    //     // Realizar la solicitud POST para actualizar el usuario
    //     $response = $this->postJson("/api/update_user/{$user->email}", $updateData, [
    //         'API-KEY' => $apikey->api_key,
    //     ]);

    //     // Verificar que la respuesta tenga el código de éxito 200
    //     $response->assertStatus(200);

    //     // Verificar que el mensaje de éxito esté presente en la respuesta
    //     $response->assertJson(['message' => 'Usuario actualizado correctamente']);

    //     // Verificar que el usuario fue actualizado en la base de datos
    //     $this->assertDatabaseHas('users', [
    //         'email' => 'newemail@example.com',
    //         'first_name' => 'Jane',
    //         'last_name' => 'Smith',
    //         'nif' => '87654321B',
    //     ]);

    //     // Verificar que el usuario tiene el rol de 'TEACHER'
    //     $this->assertTrue($user->roles()->where('code', 'TEACHER')->exists());
    // }
}
