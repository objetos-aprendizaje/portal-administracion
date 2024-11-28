<?php

namespace Tests\Unit;



use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserRoleRelationshipsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\CertificateAccessController;

class CertificateAccessControllerTest extends TestCase
{
    use RefreshDatabase;
    // public function testIndexRedirectsLoginUserExists()
    // {
    //     // Mock server variables
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
    //     $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = "test@example.com";
    //     $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = "Test";
    //     $_SERVER["SSL_CLIENT_S_DN_CN"] = "CN - 12345678";

    //     // Create a user in the database
    //     UsersModel::factory()->create([
    //         'uid' => generate_uuid(),
    //         'first_name' => 'Test',
    //         'last_name' => 'User',
    //         'nif' => '12345678',
    //         'email' => 'test@example.com',
    //         'logged_x509' => 1,
    //     ]);

    //     // Call the index method
    //     $response = $this->get(route('certificate-access'));

    //     // Assert the redirection
    //     $response->assertRedirectContains('/token_login/');
    //     $this->assertDatabaseHas('users', [
    //         'email' => 'test@example.com',
    //     ]);
    // }

    // public function testIndexCreatesUserAndRedirects()
    // {

    //      // Mock server variables
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
    //     $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = "newuser@example.com";
    //     $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = "New";
    //     $_SERVER["SSL_CLIENT_S_DN_CN"] = "CN - 87654321";

    //         // Create a new user
    //         $user = UsersModel::factory()->create([
    //             'uid' => generate_uuid(),
    //             'first_name' => 'New',
    //             'last_name' => 'User',
    //             'nif' => '87654321M',
    //             'email' => 'newuser@example.com',
    //             'logged_x509' => 1,
    //         ]);

    //         // Create a role in the database
    //         $role = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
    //         $user->roles()->attach($role->uid, [
    //             'uid' => generate_uuid(),
    //             'user_uid' => $user->uid,
    //         ]);


    //     // Call the index method
    //     $response = $this->get(route('certificate-access'));


    //     // Assert the redirection
    //     $response->assertRedirectContains('/token_login/');

    //     $this->assertDatabaseHas('users', [
    //         'email' => 'newuser@example.com',
    //     ]);
    //     $this->assertDatabaseHas('user_role_relationships', [
    //         'user_uid' => $user->uid,
    //         'user_role_uid' => $role->uid,
    //     ]);
    // }

    // public function testIndexLoginOnCertificateError()
    // {
    //     // Mock server variables
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "FAILURE";

    //     // Call the index method
    //     $response = $this->get(route('certificate-access'));

    //     // Assert the redirection to login
    //     $response->assertRedirect(env('DOMINIO_PRINCIPAL')."/login?e=certificate-error");
    // }


    // public function testIndexCreatesNewUserExistAndRedirects()
    // {

    //     putenv('DOMINIO_PRINCIPAL=http://example.com');
    //     // Simula que el certificado fue verificado
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
    //     $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = 'admin@admin.com';
    //     $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = 'Admin';
    //     $_SERVER["SSL_CLIENT_S_DN_CN"] = 'null';

    //     $response = $this->get('/certificate-access');

    //     $this->assertDatabaseHas('users', [
    //         'email' => strtolower($_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"]),
    //         'first_name' => 'Admin',
    //         'last_name' => 'User',
    //         'nif' => null,
    //         'logged_x509' => false,
    //     ]);

    //     $response->assertRedirectContains('/token_login/');
    // }

    // /** @test  PENDIENTE POR ARREGLO DE CONTROLLER*/
    // public function testCreatesNewUserAndAssignsRole()
    // {
    //     // Simula que el certificado fue verificado
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
    //     $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = 'New User';
    //     $_SERVER["SSL_CLIENT_S_DN_CN"] = '12345678A - NIF123456';
    //     $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = 'newuser@example.com';

    //     // Verifica que no hay usuarios en la base de datos antes de la prueba
    //     $this->assertFalse(UsersModel::where('email', 'newuser@example.com')->exists());

    //     $rol = UserRolesModel::where('code', 'TEACHER')->first();

    //     $response = $this->get('/certificate-access');

    //     // Verifica que se haya creado el nuevo usuario
    //     $this->assertCount(1, UsersModel::all());

    //     $user = UsersModel::where('email', $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"])->first();

    //     $this->assertNotNull($user);
    //     $this->assertEquals('New User', $user->first_name);
    //     $this->assertEquals('New User', $user->last_name);
    //     $this->assertEquals('NIF123456', $user->nif);
    //     $this->assertTrue($user->logged_x509);

    //     // Verifica que se haya creado la relación de rol
    //     $this->assertCount(1, UserRoleRelationshipsModel::all());

    //     $rol_relation = UserRoleRelationshipsModel::where('user_uid', $user->uid)->first();

    //     $this->assertNotNull($rol_relation);
    //     $this->assertEquals($user->uid, $rol_relation->user_uid);

    //     // Verifica que redirige a la URL correcta con el token
    //     $response->assertRedirectContains('/token_login/');
    // }


    // public function testIndexRedirectsToLoginOnCertificateError()
    // {
    //    // Simulamos que $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] no es "SUCCESS"
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "FAILURE";

    //     // Simulamos el dominio principal en el archivo .env
    //     $this->mockEnvVariable('DOMINIO_PRINCIPAL', 'https://example.com');

    //     // Aseguramos que cualquier estado necesario de la aplicación se refresque aquí.
    //     $this->refreshApplication();

    //     // Hacemos una petición GET a la ruta '/certificate-access'
    //     $response = $this->get('/certificate-access');

    //     // Verificamos que la redirección se hace a la URL esperada con el parámetro e=certificate-error
    //     $response->assertRedirect(env('DOMINIO_PRINCIPAL').'/login?e=certificate-error');
    // }

    // // Método para simular variables de entorno
    // protected function mockEnvVariable($key, $value)
    // {
    //     putenv("$key=$value");
    //     config([$key => $value]);
    // }

    /** @test */
    // public function testIndexWithExistingUser()
    // {
    //     // Crear un usuario de prueba
    //     $user = UsersModel::factory()->create([
    //         'email' => 'test@example.com',
    //         'logged_x509' => true
    //     ]);

    //     // Simular variables del entorno $_SERVER
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
    //     $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = 'test@example.com';

    //     // Realizar la solicitud GET
    //     $response = $this->get('/certificate-access');

    //     // Verificar la redirección
    //     $response->assertRedirect(env('DOMINIO_PRINCIPAL') . "/token_login/" . $user->fresh()->token_x509);
    // }

    // /** @test */
    // public function testIndexWithNewUser()
    // {
    //     // Simular variables del entorno $_SERVER para un nuevo usuario
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
    //     $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = 'newuser@example.com';
    //     $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = 'New';
    //     $_SERVER["SSL_CLIENT_S_DN_CN"] = '12345678A - NIF';

    //     // Crear un rol de profesor
    //     $role = UserRolesModel::factory()->create(['code' => 'TEACHER']);

    //     // Realizar la solicitud GET
    //     $response = $this->get('/certificate-access');

    //     // Verificar que el nuevo usuario se ha creado correctamente
    //     $this->assertDatabaseHas('users', [
    //         'email' => 'newuser@example.com',
    //         'first_name' => 'New',
    //         'last_name' => 'New',
    //         'nif' => 'NIF',
    //         'logged_x509' => true,
    //     ]);

    //     // Verificar la relación de rol de profesor
    //     $newUser = UsersModel::where('email', 'newuser@example.com')->first();
    //     $this->assertDatabaseHas('user_role_relationships', [
    //         'user_uid' => $newUser->uid,
    //         'user_role_uid' => $role->uid,
    //     ]);

    //     // Verificar la redirección con el token
    //     $response->assertRedirect(env('DOMINIO_PRINCIPAL') . "/token_login/" . $newUser->token_x509);
    // }

    /** @test */
    // public function testIndexWithInvalidCertificate()
    // {
    //     // Simular que el certificado no es válido
    //     $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "FAIL";

    //     // Realizar la solicitud GET
    //     $response = $this->get('/certificate-access');

    //     // Verificar que se redirige a la URL de error de certificado
    //     $response->assertRedirect(env('DOMINIO_PRINCIPAL') . "/login?e=certificate-error");
    // }
}
