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
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Testing\RefreshDatabase;


class AuthenticationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

     /**
     * @testdox Obtiene Index View Authentication Configuration
     */
    public function testIndexRouteReturnsView()
    {

        $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);// Crea roles de prueba
         $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

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
        $response = $this->get(route('administration-authentication'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se retorne la vista correcta
        $response->assertViewIs('administration.authentication_configuration');

        // Verifica que los datos se pasen correctamente a la vista
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('page_name', 'Configuración sistemas de autenticación');
        $response->assertViewHas('page_title', 'Configuración sistemas de autenticación');
        $response->assertViewHas('resources', []);
    }



}
