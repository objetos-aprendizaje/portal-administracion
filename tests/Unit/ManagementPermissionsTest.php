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

class ManagementPermissionsTest extends TestCase
{
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

    /** @test mostrar index de management_permissions */
    public function testReturnsTheManagementPermissionsView()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Asignar un rol específico al usuario (por ejemplo, el rol 'ADMINISTRATOR')
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        // Simular la carga de datos que haría el GeneralOptionsMiddleware
        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Autenticar al usuario
        Auth::login($user);

        // Simular la carga de datos que haría el middleware
        View::share('roles', $user->roles->toArray());
        
        // Realiza una solicitud GET a la ruta definida
        $response = $this->get(route('management-permissions'));

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que la vista correcta se esté utilizando
        $response->assertViewIs('administration.management_permissions');

        // Verifica que los datos pasados a la vista sean correctos
        $response->assertViewHas('page_name', 'Permisos a gestores');
        $response->assertViewHas('page_title', 'Permisos a gestores');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/management_permissions.js"
        ]);
        $response->assertViewHas('submenuselected', 'management-permissions');
    }

}
