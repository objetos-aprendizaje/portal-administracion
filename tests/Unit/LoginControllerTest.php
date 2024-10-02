<?php


use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Mail\PasswordResetMail;
use App\Models\Saml2TenantsModel;
use App\Models\TooltipTextsModel;
use Illuminate\Support\MessageBag;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use App\Models\ResetPasswordTokensModel;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\LoginController;
use Laravel\Socialite\Two\User as twouser;
use Illuminate\Foundation\Testing\RefreshDatabase;


class LoginControllerTest extends TestCase
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
        // Define la ruta para la prueba
        Route::get('auth/google', [LoginController::class, 'redirectToGoogle']);
        Route::get('auth/twitter', [LoginController::class, 'redirectToTwitter']);
        Route::get('auth/linkedin',  [LoginController::class, 'redirectToLinkedin']);
        Route::get('auth/facebook', [LoginController::class, 'redirectToFacebook']);
    }

    /** @test Autenticación*/
    public function testAuthenticateSuccessful()
    {
        $user = UsersModel::factory()->create([
            'password' => Hash::make('1234')
        ]);
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Realiza una solicitud POST a la ruta de autenticación
        $response = $this->post('/login/authenticate', [
            'email' => $user->email,
            'password' => '1234',
        ]);


    // Verifica que la respuesta sea correcta
    $response->assertStatus(200);
    $this->assertEquals(['authenticated' => true], $response->json());

    }



    /** @test Redirección Google*/
    public function testRedirectToGoogle()
    {
        // Simula la redirección a Google
        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturn(new \Illuminate\Http\RedirectResponse('/redirect-url'));

        // Realiza una solicitud GET a la ruta de redirección
        $response = $this->get('/auth/google');

        // Verifica que la respuesta sea una redirección
        $response->assertRedirect('/redirect-url');
    }

    /** @test redirección Twitter*/
    public function testRedirectToTwitter()
    {
        // Simula la redirección a Google
        Socialite::shouldReceive('driver')
            ->with('twitter')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturn(new \Illuminate\Http\RedirectResponse('/redirect-url'));

        // Realiza una solicitud GET a la ruta de redirección
        $response = $this->get('/auth/twitter');

        // Verifica que la respuesta sea una redirección
        $response->assertRedirect('/redirect-url');
    }


    /** @test redirección Twitter*/
    public function testRedirectToLinkedin()
    {
        // Simula la redirección a Google
        Socialite::shouldReceive('driver')
            ->with('linkedin-openid')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturn(new \Illuminate\Http\RedirectResponse('/redirect-url'));

        // Realiza una solicitud GET a la ruta de redirección
        $response = $this->get('/auth/linkedin-openid');

        // Verifica que la respuesta sea una redirección
        $response->assertRedirect('/redirect-url');
    }


    /** @test Redirect Facebook*/
    public function testRedirectToFacebook()
    {
        // Simula la redirección a Google
        Socialite::shouldReceive('driver')
            ->with('facebook')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturn(new \Illuminate\Http\RedirectResponse('/redirect-url'));

        // Realiza una solicitud GET a la ruta de redirección
        $response = $this->get('/auth/facebook');

        // Verifica que la respuesta sea una redirección
        $response->assertRedirect('/redirect-url');
    }


    /** @test Logout*/
    public function testLogout()
    {
        // Simular que el usuario está autenticado
        $user = UsersModel::factory()->create()->latest()->first();
        $this->actingAs($user);

        // Establecer las variables de sesión necesarias
        Session::put('google_id', '123456');
        Session::put('token_google', 'token_google');

        // Realizar una solicitud GET a la ruta de logout
        $response = $this->get('/logout');

        // Verificar que el usuario ha sido deslogueado
        $this->assertFalse(Auth::check());

        // Verificar que la sesión ha sido limpiada
        $this->assertNull(Session::get('google_id'));
        $this->assertNull(Session::get('token_google'));

        // Verificar que se redirige a la URL de inicio de sesión
        $response->assertRedirect(env('APP_URL') . '/login');
    }

    /** @test reset Password Envio de correo*/
    public function testResetPasswordWithValidToken()
    {
        // Simular la creación de un usuario
        $user = UsersModel::factory()->create([
            'password' => bcrypt('oldpassword'), // Contraseña antigua
        ]);

        // Simular la creación de un token de restablecimiento
        $resetToken = ResetPasswordTokensModel::factory()->create([
            'uid' => generate_uuid(),
            'uid_user' => $user->uid,
            'email' => $user->email,
            'token' => 'valid-token',
            'expiration_date' => Carbon::now()->addMinutes(60)->format('Y-m-d\TH:i')
        ])->latest()->first();

        // Realizar una solicitud POST a la ruta de restablecimiento de contraseña
        $response = $this->post('/reset_password/send', [
            'token' => $resetToken->token,
            'password' => 'newpassword',
        ]);

        // Verificar que el usuario tenga la nueva contraseña
        $user->refresh(); // Recargar el usuario desde la base de datos
        $this->assertTrue(password_verify('newpassword', $user->password));

        // Verificar que el token haya sido invalidado
        $resetToken->refresh(); // Recargar el token desde la base de datos
        $this->assertNotNull($resetToken->expiration_date); // Debería tener una fecha de expiración actualizada

        // Verificar que se redirige a la ruta de login con un mensaje de éxito
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', ['Se ha restablecido la contraseña']);
    }

    /** @test recovery Password Envio de correo*/
    public function testRecoverPasswordSendsEmail()
    {
        // Simular el envío de correos electrónicos
        Mail::fake();

        // Realizar la solicitud POST a la ruta con un email no existente
        $response = $this->post('/recover_password/send', [
            'email' => 'nonexistent@example.com',
        ]);

        // Verificar que se redirige a la ruta de inicio de sesión
        $response->assertRedirect(route('login'));

        // Verificar que se muestra el mensaje de éxito (aún se redirige aunque no se envíe el correo)
        $response->assertSessionHas('success', ['Se ha enviado un email para reestablecer la contraseña']);

        // Verificar que no se envió ningún correo electrónico
        Mail::assertNothingSent();
    }

    /** @test reset Password con token*/
    public function testResetPasswordWithValidData()
    {
        // Crear un usuario y un token de restablecimiento de contraseña
        $user = UsersModel::factory()->create();
        $token = ResetPasswordTokensModel::factory()->create([
            'uid' => generate_uuid(),
            'uid_user' => $user->uid,
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'token' => generate_uuid()
        ]);

        // Datos de prueba
        $data = [
            'token' => $token->token,
            'password' => 'newpassword123',
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->post('password/reset', $data);

        // Verificar que se redirige a la ruta de inicio de sesión
        $response->assertRedirect(route('login'));

        // Verificar que se muestra el mensaje de éxito
        $response->assertSessionHas('success', ['Se ha restablecido la contraseña']);

        // Verificar que la contraseña se ha actualizado
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));

        // Verificar que el token ha expirado
        $this->assertNotNull($token->fresh()->expiration_date);
    }

    /** @test Reset Password Token invalido*/
    public function testResetPasswordWithInvalidToken()
    {
        // Datos de prueba con un token inválido
        $data = [
            'token' => 'invalid_token',
            'password' => 'newpassword123',
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->post('password/reset', $data);

        // Verificar que se redirige a la ruta de inicio de sesión
        $response->assertRedirect(route('login'));

        // Verificar que se muestra el mensaje de error
        $response->assertSessionHas('reset', false);
        $response->assertSessionHas('message', 'El token no es válido');
    }

    /** @test Reset Password con Token expirado*/
    public function testResetPasswordWithExpiredToken()
    {
        // Crear un usuario y un token de restablecimiento de contraseña expirado
        $user = UsersModel::factory()->create();
        $token = ResetPasswordTokensModel::factory()->create([
            'uid' => generate_uuid(),
            'uid_user' => $user->uid,
            'expiration_date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);

        // Datos de prueba
        $data = [
            'token' => $token->token,
            'password' => 'newpassword123',
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->post('password/reset', $data);

        // Verificar que se redirige a la ruta de inicio de sesión
        $response->assertRedirect(route('login'));
    }

    /** @test Reset Password con password inválido*/
    public function testResetPasswordWithInvalidPassword()
    {
        // Crear un usuario y un token de restablecimiento de contraseña
        $user = UsersModel::factory()->create();
        $token = ResetPasswordTokensModel::factory()->create([
            'uid' => generate_uuid(),
            'uid_user' => $user->uid,
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // Datos de prueba con una contraseña inválida
        $data = [
            'token' => $token->token,
            'password' => 'short',
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->post('password/reset', $data);

        // Verificar que se redirige de vuelta a la misma ruta
        $response->assertRedirect();

        // Verificar que se muestran los errores de validación
        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     * Verifica que la vista de recuperación de contraseña se carga correctamente con los datos esperados.
     */
    public function testIndexLoadsRecoverPasswordViewWithCorrectData()
    {
        // Simular la existencia de $general_options si es necesario
        $general_options = [
            'site_name' => 'MyApp',
        ];

        // Simular el compartir de la variable $general_options
        view()->share('general_options', $general_options);

        // Crear una instancia de MessageBag para simular errores
        $errors = session()->get('errors', new \Illuminate\Support\MessageBag());

        // Compartir manualmente la variable $errors con la vista
        view()->share('errors', $errors);

        // Realizar la petición a la ruta
        $response = $this->get(route('recover-password'));

        // Verificar el código de estado 200
        $response->assertStatus(200);

        // Verificar que se carga la vista correcta
        $response->assertViewIs('non_authenticated.recover_password');

        // Verificar que los datos esperados están presentes en la vista
        $response->assertViewHas('page_name', 'Restablecer contraseña');
        $response->assertViewHas('page_title', 'Restablecer contraseña');
        $response->assertViewHas('resources', [
            "resources/js/recover_password.js",
        ]);

        // Verificar que $general_options está disponible en la vista
        $response->assertViewHas('general_options', $general_options);
    }
    public function testIndexReturnsViewWithCorrectData()
    {

        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Asignar un rol específico al usuario (por ejemplo, el rol 'ADMINISTRATOR')
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        // Autenticar al usuario
        Auth::login($user);

        // Simular la carga de datos que haría el middleware
        View::share('roles', $user->roles->toArray());

        // Crear un MessageBag vacío en lugar de un Collection
        $errores = new MessageBag();

        // Compartir el MessageBag con la vista
        View::share('errors', $errores);

        // Simulamos los valores en la base de datos para `poa_logo`
        GeneralOptionsModel::create([
            'option_name' => 'poa_logo',
            'option_value' => 'test_logo.png',
        ]);

        // Simulamos los valores en la base de datos para `cas_active`
        GeneralOptionsModel::create([
            'option_name' => 'cas_active',
            'option_value' => 1,
        ]);

        // Simulamos los valores en la base de datos para `rediris_active`
        GeneralOptionsModel::create([
            'option_name' => 'rediris_active',
            'option_value' => 1,
        ]);

        // Simulamos los valores en la base de datos para `Saml2TenantsModel` (CAS y RedIRIS)
        $casTenant = Saml2TenantsModel::factory()->create([
            'key' => 'cas',
            'uuid' => generate_uuid(),
        ]);

        $redirisTenant = Saml2TenantsModel::factory()->create([
            'key' => 'rediris',
            'uuid' => generate_uuid(),
        ]);

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();

        app()->instance('general_options', $general_options);

        View::share('general_options', $general_options);



        // Configura la variable de entorno
        config()->set('app.dominiocertificado', 'https://example-cert.com');

            // Realiza una petición GET a la ruta '/login'
        $response = $this->get('/login');

        // Verifica que la vista se carga correctamente y contiene los datos esperados
        $response->assertStatus(200);
        $response->assertViewIs('non_authenticated.login');
        // Verificaciones individuales para evitar el error
        $response->assertViewHas('page_name', 'Inicia sesión');
        $response->assertViewHas('page_title', 'Inicia sesión');
        $response->assertViewHas('logo', 'test_logo.png');
        $response->assertViewHas('resources', ['resources/js/login.js']);
    }

    public function testValidatesLoginMethod()
    {

        // Mocking Socialite user response
        $mockUser = (object) [
            'email' => 'admin@admin.com',
            'name' => 'Test User'
        ];

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('user')
            ->once()
            ->andReturn($mockUser);

        $login_method = 'google';
        // Call the controller method through the route
        $response = $this->get('/auth/callback/'.$login_method);

        // Assert that the response redirects to the home page
        $response->assertRedirect('/');
    }

        /** @test Autenticación fallida*/
        public function testAuthenticateFailed()
        {
            // Intenta autenticar con credenciales incorrectas
            $response = $this->post('/login/authenticate', [
                'email' => 'nonexistent@example.com',
                'password' => 'wrongpassword',
            ]);

            // Verifica que la respuesta sea un error
            $response->assertStatus(401);
            $this->assertEquals(['authenticated' => false, 'error' => 'No hay ninguna cuenta asociada al email'], $response->json());
        }


        public function testLogoIsNull()
        {
            // Simulamos que no hay opción para el logo
            GeneralOptionsModel::factory()->create([
                'option_name' => 'poa_logo',
                'option_value' => null,
            ]);

            // Simular el compartir de la variable $general_options
            $general_options = [
                'poa_logo_1' => null,
            ];

            // Compartir la variable con la vista
            view()->share('general_options', $general_options);

            // Crear una instancia de MessageBag para simular errores
            $errors = session()->get('errors', new MessageBag());

            // Compartir manualmente la variable $errors con la vista
            view()->share('errors', $errors);

            $this->withoutExceptionHandling();

            // No creamos ninguna opción para 'poa_logo'
            $response = $this->get('/login');

            $response->assertStatus(200);
            $response->assertViewHas('logo', null);
        }

        public function testUrlCasGeneratedWhenLoginCasActive()
    {

        // Simulamos que no hay opción para el logo
        GeneralOptionsModel::factory()->create([
            'option_name' => 'poa_logo',
            'option_value' => null,
        ]);

        // Simular el compartir de la variable $general_options
        $general_options = [
            'poa_logo_1' => null,
        ];

        // Compartir la variable con la vista
        view()->share('general_options', $general_options);

        // Crear una instancia de MessageBag para simular errores
        $errors = session()->get('errors', new MessageBag());

        // Compartir manualmente la variable $errors con la vista
        view()->share('errors', $errors);

        $this->withoutExceptionHandling();

        // Simulamos que CAS está activo
        GeneralOptionsModel::where('option_name', 'cas_active')->update([
            'option_value' => 1,
        ]);

        // Simulamos la entrada en Saml2TenantsModel
        $saml2Tenant = Saml2TenantsModel::factory()->create([
            'key' => 'cas',
        ]);

        // Llama a la ruta que ejecuta el método index del controlador
        $response = $this->get('/login');

        // Verifica que el estado de la respuesta sea 200
        $response->assertStatus(200);

        // Verifica que la URL se genere correctamente
        $expectedUrl = url('saml2/' . $saml2Tenant->uuid . '/login');
        $response->assertViewHas('urlCas', $expectedUrl);
    }

    public function testUrlRedirisIsActive()
    {

        // Simulamos que no hay opción para el logo
        GeneralOptionsModel::factory()->create([
            'option_name' => 'poa_logo',
            'option_value' => null,
        ]);

        // Simular el compartir de la variable $general_options
        $general_options = [
            'poa_logo_1' => null,
        ];

        // Compartir la variable con la vista
        view()->share('general_options', $general_options);

        // Crear una instancia de MessageBag para simular errores
        $errors = session()->get('errors', new MessageBag());

        // Compartir manualmente la variable $errors con la vista
        view()->share('errors', $errors);

        $this->withoutExceptionHandling();

        // Simulamos que CAS está activo
        GeneralOptionsModel::where('option_name', 'rediris_active')->update([
            'option_value' => 1,
        ]);

        // Simulamos la entrada en Saml2TenantsModel
        $saml2Tenant = Saml2TenantsModel::factory()->create([
            'key' => 'rediris',
        ]);

        // Llama a la ruta que ejecuta el método index del controlador
        $response = $this->get('/login'); // Asegúrate de que esta ruta es correcta

        // Verifica que el estado de la respuesta sea 200
        $response->assertStatus(200);

    }

    /** @test */
    public function testRedirectsHomeTokenValid()
    {
        // Arrange: Crea un usuario con un token válido
       $user = UsersModel::factory()->create(['uid' => generate_uuid(),'token_x509' => 'valid-token'])->first();


        // Act: Realiza la solicitud al endpoint con el token válido
        $this->get('/token_login/valid-token');

        // Assert: Verifica que el usuario esté autenticado
        $this->assertTrue(Auth::check());


        // Verifica que el token haya sido eliminado
        $user->refresh();
        $this->assertEmpty($user->token_x509);
    }

     /** @test */
     public function testRedirectsLoginTokenInvalid()
     {

        UsersModel::factory()->create(['uid' => generate_uuid(),'token_x509' => ''])->first();
        // Act: Realiza la solicitud al endpoint con un token inválido
        $response = $this->get('/token_login/ ');

        // Assert: Verifica que no haya un usuario autenticado
        $this->assertFalse(Auth::check());

        // Verifica que sea redirigido a la página de login con un error específico
        //$response->assertRedirect(env('DOMINIO_PRINCIPAL') . '/login?e=certificate-error');
     }

      /** @test */
    public function testExceptionInvalidLoginMethods()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Método de login no válido');

        $authController = new LoginController();

        // Métodos inválidos
        $invalidMethods = ['instagram', 'github', 'yahoo'];

        foreach ($invalidMethods as $method) {
            // Debe lanzar excepción
            $authController->handleSocialCallback($method);
        }
    }

    /** @test */
    public function testReturnsErrorCredentialsInvalid()
    {
        // Simula una solicitud con credenciales inválidas
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/login/authenticate', $credentials);

        // Verifica que se retorne una respuesta JSON con autenticación falsa
        $response->assertStatus(401);
        $response->assertJson(['authenticated' => false]);

    }

     /** @test */
     public function testReturnsErrorWhenUserNotExist()
     {
           // Crea un usuario en la base de datos
        $user = UsersModel::factory()->create([
            'email' => 'existent@example.com',
            'password' => Hash::make('correctpassword'), // Contraseña correcta
        ])->first();

        // Simula una solicitud con credenciales incorrectas (contraseña incorrecta)
        $credentials = [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/login/authenticate', $credentials);

        // Verifica que se retorne una respuesta JSON con autenticación falsa
        $response->assertStatus(401);
        $response->assertJson(['authenticated' => false]);
        $response->assertJson([
            'error' => 'No se ha encontrado ninguna cuenta con esas credenciales',
        ]);

    }

}




