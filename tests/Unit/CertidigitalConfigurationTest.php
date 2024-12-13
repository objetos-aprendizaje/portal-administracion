<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;


class CertidigitalConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }


    public function testIndexRouteReturnsViewCertidigitalConfiguration()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);

        // Realiza una solicitud GET a la ruta
        $response = $this->get(route('certidigital-configuration'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se retorne la vista correcta
        $response->assertViewIs('administration.certidigital');

        // Verifica que los datos se pasen correctamente a la vista
        $response->assertViewHas('page_name', 'Configuración de Certidigital');
        $response->assertViewHas('page_title', 'Configuración de Certidigital');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/certidigital_configuration.js"
        ]);
        $response->assertViewHas('submenuselected', 'certidigital');
    }

    /**
     * @test  Guarda CertidigitalConfiguration */

    public function testSaveCertidigitalForm()
    {
        // Simular el inicio de sesión del usuario

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        // Datos de prueba para el formulario
        $formData = [
            'certidigital_url' => 'https://example.com',
            'certidigital_url_token' => 'https://example.com',
            'certidigital_center_id' => '100',
            'certidigital_organization_oid' => '100',
            'certidigital_client_id' => 'client_id',
            'certidigital_client_secret' => 'client_secret',
            'certidigital_username' => 'username',
            'certidigital_password' => 'password',
        ];

        // Hacer una solicitud POST a la ruta
        $response = $this->post('/administration/certidigital/save_certidigital_form', $formData);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene el mensaje de éxito
        $response->assertJson(['message' => 'Configuración de certidigital correctamente']);

        // Comprobar que los datos se hayan guardado correctamente en la base de datos
        $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_url', 'option_value' => 'https://example.com']);
        $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_client_id', 'option_value' => 'client_id']);
        $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_client_secret', 'option_value' => 'client_secret']);
        $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_username', 'option_value' => 'username']);
        $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_password', 'option_value' => 'password']);
    }

    /**
     * @test Eroor 422 al tratar de Guardar CertidigitalConfiguration */

    public function testSaveCertidigitalWithError422() {

        // Simular el inicio de sesión del usuario

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $formData = [
            'certidigital_url' => 'https://example.com',
        ];

        // Hacer una solicitud POST a la ruta
        $response = $this->post('/administration/certidigital/save_certidigital_form', $formData);

         // Comprobar que la respuesta es correcta
         $response->assertStatus(422);

         // Comprobar que la respuesta contiene el mensaje correcto
        $response->assertJson(['message' => 'Algunos campos son incorrectos']);

    }
}
