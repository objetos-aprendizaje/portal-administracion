<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnaliticsUsersControllerTest extends TestCase
{
    use RefreshDatabase;

/**
 * @testdox Inicialización de inicio de sesión
 */
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    public function testIndexViewAnaliticsUsers()
    {

        $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);// Crea roles de prueba
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

        // Crear datos de prueba: agregar usuarios
        UsersModel::factory()->count(5)->create();

        // Realizar la solicitud a la ruta
        $response = $this->get(route('analytics-users'));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertViewIs('analytics.index');
        $response->assertViewHas('page_name', 'Analíticas de usuarios');
        $response->assertViewHas('page_title', 'Analíticas de usuarios');
        $response->assertViewHas('resources', [
            "resources/js/analytics_module/analytics_users.js"
        ]);
        $response->assertViewHas('total_users', 7);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'analytics-users');
    }

    public function testGetUsersRoles()
    {

        if (UserRolesModel::count() < 4) {
            UserRolesModel::create(['name' => 'Administrator']);
            UserRolesModel::create(['name' => 'Management']);
            UserRolesModel::create(['name' => 'Student']);
            UserRolesModel::create(['name' => 'Teacher']);
        }

        // Obtener hasta 3 roles
        UserRolesModel::take(3)->get();

        // Realizar la solicitud a la ruta
        $response = $this->get(route('analytics-users-roles', ['size' => 1]));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);

        // Asegurarse de que la respuesta es un array
        $responseData = $response->json();
        $this->assertIsArray($responseData, 'La respuesta no es un array.');


    }




}
