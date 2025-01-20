<?php


use Tests\TestCase;

use App\Models\UsersModel;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Schema;
use App\Models\ResetPasswordTokensModel;

use Illuminate\Foundation\Testing\RefreshDatabase;


class RecoverPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @test
     * Prueba que la vista de restablecer contraseña se carga correctamente cuando el token es válido.
     */
    public function testIndexLoadsResetPasswordPageWithValidToken()
    {
        // Crear un usuario para probar
        $user = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'email' => 'test@example.com',
        ]);

        // Crear un token de restablecimiento de contraseña válido
        ResetPasswordTokensModel::factory()->create([
            'token' => 'valid-token',
            'expiration_date' => now()->addMinutes(30),
            'uid_user' => $user->uid,
        ]);

        $errors = session()->get('errors', new \Illuminate\Support\MessageBag());

        // Compartir manualmente la variable $errors con la vista
        view()->share('errors', $errors);

        // Hacer una solicitud GET a la ruta con el token válido
        $response = $this->get(route('password.reset', ['token' => 'valid-token']));

        // Verificar que la respuesta sea 200 (éxito)
        $response->assertStatus(200);

        // Verificar que se carga la vista correcta
        $response->assertViewIs('non_authenticated.reset_password');

        // Verificar que la vista contiene los recursos de JavaScript correctos
        $response->assertViewHas('resources', [
            'resources/js/reset_password.js',
        ]);

        // Verificar que el token y el email se pasan a la vista correctamente
        $response->assertViewHas('token', 'valid-token');
    }

    /**
     * @test
     * Prueba que redirige a la página de login cuando el token ha expirado.
     */
    public function testIndexRedirectsToLoginWhenTokenIsExpired()
    {
        // Crear un usuario para probar
        $user = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'email' => 'test@example.com',
        ]);


        // Crear un token de restablecimiento de contraseña expirado
        $resetPasswordToken = ResetPasswordTokensModel::factory()->create([
            'token' => 'expired-token',
            'expiration_date' => now()->subMinutes(30), // Token expirado
            'uid_user' => $user->uid,
        ]);

        // Hacer una solicitud GET a la ruta con el token expirado
        $response = $this->get(route('password.reset', ['token' => 'expired-token']));

        // Verificar que se redirige a la ruta de login
        $response->assertRedirect(route('login'));

        // Verificar que los datos correctos están presentes en la sesión
        $response->assertSessionHas('link_recover_password_expired', true);
        $response->assertSessionHas('email', $resetPasswordToken->email);
    }


    /** @test reenvio email*/

    public function testResendEmailPasswordResetSuccessfully()
    {
        // Simular el envío de correos electrónicos
        Mail::fake();

        // Crear un usuario para probar
        $user = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'email' => 'test@example.com',
        ]);

        $this->assertNotEquals('admin@admin.com', $user->email);

        $tokenpass = ResetPasswordTokensModel::factory()->create([
            'uid_user' => $user->uid,
            'email' => $user->email
        ]);


        // Realizar la solicitud POST a la ruta con el email del usuario
        $response = $this->post('/register/resend_email_confirmation', [
            'email' => $tokenpass->email,
        ]);

        // Verificar que la respuesta sea JSON y tenga el mensaje esperado
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Se ha reenviado el email de restablecimiento de contraseña'
            ]);

        // Verificar que se haya creado un token en la base de datos
        $this->assertDatabaseHas('reset_password_tokens', [
            'email' => $user->email,
        ]);
    }
}
