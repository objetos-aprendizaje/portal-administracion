<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CentersModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\LmsSystemsModel;
use App\Models\LicenseTypesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdministrationGeneralOptionsTest extends TestCase
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

/** Group Lanes show*/

/**
*  @test  Obtener Index View Lanes Show */



    public function testIndexViewLanesShow()
    {

        // Crear un usuario de prueba y asignar roles
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

        // Simular la ruta
        $response = $this->get(route('lanes-show'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta fue cargada
        $response->assertViewIs('administration.lanes_show');

        // Verificar que los datos de la vista sean los correctos
        $response->assertViewHas('page_name', 'Carriles a mostrar');
        $response->assertViewHas('page_title', 'Carriles a mostrar');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/lanes_show.js"
        ]);
        $response->assertViewHas('submenuselected', 'lanes-show');
    }



/**
*  @test  Guardar Lanes Show */
    public function testSaveLanesShow()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Datos de prueba
        $data = [
            'lane_featured_courses' => true,
            'lane_featured_educationals_programs' => false,
            'lane_recents_educational_resources' => true,
            'lane_featured_itineraries' => false,
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/lanes_show/save_lanes_show', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
                ->assertJson(['message' => 'Preferencias de carriles actualizados correctamente']);

        // Verificar que los datos se hayan guardado en la base de datos
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_featured_courses',
            'option_value' => true,
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_featured_educationals_programs',
            'option_value' => false,
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_recents_educational_resources',
            'option_value' => true,
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_featured_itineraries',
            'option_value' => false,
        ]);
    }


/** Group LMS Systems*/
/** @test Obtener Index View */

    public function testIndexRouteReturnsViewLms()
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

        // Realiza una solicitud GET a la ruta
        $response = $this->get(route('lms-systems'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se retorne la vista correcta
        $response->assertViewIs('administration.lms_systems.index');

        // Verifica que los datos se pasen correctamente a la vista
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('page_name', 'Sistemas LMS');
        $response->assertViewHas('page_title', 'Sistemas LMS');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/lms_systems.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'lms-systems');
    }
/**
 * @test  Guardar Lms Systems */
    public function testSaveLmsSystemCreatesNewLms()
    {
        // Crear un usuario y autenticar
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $lms = LmsSystemsModel::factory()->create()->first();
        $this->assertDatabaseHas('lms_systems', ['uid' => $lms->uid]);


        // Enviar una solicitud para crear un nuevo sistema LMS
        $data = [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ];

        $response = $this->postJson('/administration/lms_systems/save_lms_system', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                ->assertJson(['message' => 'LMS añadido correctamente']);

        // Verificar que el sistema LMS se haya creado en la base de datos
        $this->assertDatabaseHas('lms_systems', [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ]);
    }

/**
*  @test  Actualiza LMS  */
    public function testSaveLmsSystemUpdatesExistingLms()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $lms = LmsSystemsModel::factory()->create()->first();
        $this->assertDatabaseHas('lms_systems', ['uid' => $lms->uid]);
        // Enviar una solicitud para crear un nuevo sistema LMS
        $data = [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ];

        // Enviar una solicitud para actualizar el sistema LMS existente
        $response = $this->postJson('/administration/lms_systems/save_lms_system', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);

        // Verificar que el sistema LMS se haya actualizado en la base de datos
        $this->assertDatabaseHas('lms_systems', [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ]);
    }

/**
*  @test  Guardar LMS campos obligatorio error */
    public function testSaveLmsSystemValidationFails()
    {
        // Crear un usuario y autenticar
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Enviar una solicitud con datos inválidos (sin nombre ni identificador)
        $response = $this->postJson('/administration/lms_systems/save_lms_system', []);

        // Verificar que la respuesta sea un error de validación
        $response->assertStatus(422)
                    ->assertJsonStructure(['message', 'errors']);
    }

/**
*  @test  Elimina LMS  */

    public function testDeleteLmsSystems()
        {
            // Crear un usuario y autenticar
            $user = UsersModel::factory()->create();
            $this->actingAs($user);

            // Crear algunos sistemas LMS para eliminar
            $lms1 = LmsSystemsModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Sistema LMS 1',
                'identifier' => 'lms-1',
            ]);

            $lms2 = LmsSystemsModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Sistema LMS 2',
                'identifier' => 'lms-2',
            ]);

            // Enviar una solicitud para eliminar los sistemas LMS
            $response = $this->deleteJson('/administration/lms_systems/delete_lms_systems', [
                'uids' => [$lms1->uid, $lms2->uid], // Enviar solo los UIDs
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                     ->assertJson(['message' => 'LMS eliminados correctamente']);

            // Verificar que los sistemas LMS se hayan eliminado de la base de datos
            $this->assertDatabaseMissing('lms_systems', [
                'uid' => $lms1->uid,
            ]);
            $this->assertDatabaseMissing('lms_systems', [
                'uid' => $lms2->uid,
            ]);

    }


/** @test  Obtener LMS por uid*/
    public function testGetLmsSystemByUid()
    {
        // Crear un sistema LMS en la base de datos
        $lmsSystem = LmsSystemsModel::factory()->create()->first();

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/lms_systems/get_lms_system/' . $lmsSystem->uid);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene los datos esperados
        $data = $response->json();
        $this->assertEquals($lmsSystem->uid, $data['uid']);
        $this->assertEquals($lmsSystem->name, $data['name']);
        $this->assertEquals($lmsSystem->identifier, $data['identifier']);
    }

/** @test  Lista LMS conp parámetros por defecto*/
     public function testListLmsSystemsWithDefaultParameters()
     {

        LmsSystemsModel::factory()->count(3)->create();


         $response = $this->get('/administration/lms_systems/get_lms_systems');

         $response->assertStatus(200);
         $response->assertJsonStructure([
             'current_page',
             'data' => [
                 '*' => [
                     'uid',
                     'name',
                 ],
             ],
             'last_page',
             'per_page',
             'total',
         ]);
     }

/** @test */
    public function testFilterLmsSystemsBySearch()
    {

        LmsSystemsModel::factory()->create(['name' => 'LMS System 1']);
        LmsSystemsModel::factory()->create(['name' => 'Another System']);
        $response = $this->get('/administration/lms_systems/get_lms_systems?search=LMS');

        $response->assertStatus(200);

    }

/** @test Ordenar lista LMS*/
    public function testSortLmsSystems()
    {
        LmsSystemsModel::factory()->create(['name' => 'LMS System 2']);
        LmsSystemsModel::factory()->create(['name' => 'Another System']);


        $response = $this->get('/administration/lms_systems/get_lms_systems?sort[0][field]=name&sort[0][dir]=asc&size=10');

        $response->assertStatus(200);

    }

}
