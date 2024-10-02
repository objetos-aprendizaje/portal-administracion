<?php

namespace Tests\Unit;



use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\UserRoleRelationshipsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\CertificateAccessController;

class CertificateAccessControllerTest extends TestCase
{
    use RefreshDatabase;
    public function testIndexRedirectsLoginUserExists()
    {
        // Mock server variables
        $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
        $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = "test@example.com";
        $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = "Test";
        $_SERVER["SSL_CLIENT_S_DN_CN"] = "CN - 12345678";

        // Create a user in the database
        UsersModel::factory()->create([
            'uid' => generate_uuid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'nif' => '12345678',
            'email' => 'test@example.com',
            'logged_x509' => 1,
        ]);

        // Call the index method
        $response = $this->get(route('certificate-access'));

        // Assert the redirection
        $response->assertRedirectContains('/token_login/');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function testIndexCreatesUserAndRedirects()
    {

         // Mock server variables
        $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";
        $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = "newuser@example.com";
        $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = "New";
        $_SERVER["SSL_CLIENT_S_DN_CN"] = "CN - 87654321";

            // Create a new user
            $user = UsersModel::factory()->create([
                'uid' => generate_uuid(),
                'first_name' => 'New',
                'last_name' => 'User',
                'nif' => '87654321M',
                'email' => 'newuser@example.com',
                'logged_x509' => 1,
            ]);

            // Create a role in the database
            $role = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
            $user->roles()->attach($role->uid, [
                'uid' => generate_uuid(),
                'user_uid' => $user->uid,
            ]);


        // Call the index method
        $response = $this->get(route('certificate-access'));


        // Assert the redirection
        $response->assertRedirectContains('/token_login/');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
        $this->assertDatabaseHas('user_role_relationships', [
            'user_uid' => $user->uid,
            'user_role_uid' => $role->uid,
        ]);
    }

    public function testIndexLoginOnCertificateError()
    {
        // Mock server variables
        $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "FAILURE";

        // Call the index method
        $response = $this->get(route('certificate-access'));

        // Assert the redirection to login
        $response->assertRedirect(env('DOMINIO_PRINCIPAL')."/login?e=certificate-error");
    }


    public function testIndexCreatesNewUserAndRedirects()
    {
         // Simulamos que $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] es "SUCCESS"
    $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "SUCCESS";

    // Simulamos datos de certificado SSL en $_SERVER
    $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"] = "Test Name";
    $_SERVER["SSL_CLIENT_S_DN_CN"] = "CN=Some Info - 12345678A";
    $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"] = "test@example.com";


    // Verificamos que no existe un usuario con el email proporcionado
    $this->assertDatabaseMissing('users', [
        'email' => 'test@example.com',
    ]);

    // Hacemos una petición GET a la ruta '/certificate-access'
    $response = $this->get('/certificate-access');

    // Verificamos que el usuario se ha creado correctamente en la base de datos
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'first_name' => 'Test Name',
        'last_name' => 'Test Name',
        'nif' => '12345678A',
        'logged_x509' => true,
    ]);

    // Verificamos que la relación de rol se ha creado correctamente
    $user = UsersModel::where('email', 'test@example.com')->first();

    $this->assertNotNull($user->uid);
    $this->assertMatchesRegularExpression('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $user->uid);

    // Verificamos que el rol se haya asignado correctamente
    $roleRelation = UserRoleRelationshipsModel::where('user_uid', $user->uid)->first();
    $this->assertNotNull($roleRelation);
    $this->assertEquals($roleRelation->user_role_uid, UserRolesModel::where('code', 'TEACHER')->first()->uid);



    }

    public function testIndexRedirectsToLoginOnCertificateError()
    {
        // Simulamos que $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] no es "SUCCESS"
        $_SERVER["REDIRECT_SSL_CLIENT_VERIFY"] = "FAILURE";

        // Simulamos el dominio principal en el archivo .env
        $this->mockEnvVariable('DOMINIO_PRINCIPAL', 'https://example.com');

        // Hacemos una petición GET a la ruta '/certificate-access'
        $response = $this->get('/certificate-access');

        // Verificamos que la redirección se hace a la URL esperada con el parámetro e=certificate-error
        $response->assertRedirect('https://example.com/login?e=certificate-error');
    }

    // Método para simular variables de entorno
    protected function mockEnvVariable($key, $value)
    {
        putenv("$key=$value");
        $this->app->make('config')->set('app.env', $value);
    }



}
