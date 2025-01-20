<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;


class ApiKeyControllerTest extends TestCase
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

    /**@group Apikey */

    /** @test  Obtener Index View Apikey Exitoso*/

    public function testIndexRouteReturnsViewApikey()
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
        $response = $this->get(route('api-keys'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se retorne la vista correcta
        $response->assertViewIs('administration.api_keys.index');

        // Verifica que los datos se pasen correctamente a la vista
        $response->assertViewHas('page_name', 'Claves de API');
        $response->assertViewHas('page_title', 'Claves de API');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/api_keys.js",
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'api-keys');
    }

    /** @test  Guardar Apikey Exitoso*/
    public function testSaveApiKeySuccessfully()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de la solicitud
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        $data = [
            'uid' => $apikey->uid,
            'name' => $apikey->name,
            'api_key' => $apikey->api_key,
        ];

        // Realiza la solicitud POST
        $response = $this->postJson('/administration/api_keys/save_api_key', $data);

        // Verifica la respuesta
        $response->assertStatus(200)
                ->assertJson(['message' => 'Clave guardada correctamente']);

        // Verifica que la clave se haya guardado en la base de datos
        $this->assertDatabaseHas('api_keys', [
            'uid' => $apikey->uid,
            'name' => $apikey->name,
            'api_key' => $apikey->api_key,
        ]);


        // Actualizar
        $data = [
            'api_key_uid' => $apikey->uid,
            'name' => 'nombre de clave actualizada',
            'api_key' => $apikey->api_key,
        ];


         // Realiza la solicitud POST
         $response = $this->postJson('/administration/api_keys/save_api_key', $data);

         // Verifica la respuesta
         $response->assertStatus(200)
                 ->assertJson(['message' => 'Clave guardada correctamente']);




    }

    /** @test  Guardar Apikey con error*/
    public function testSaveApiKeyWithError422()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de la solicitud
        $apikey = ApiKeysModel::factory()->create()->first();

        $data = [
            'api_key' => $apikey->api_key,
        ];

        // Realiza la solicitud POST
        $response = $this->postJson('/administration/api_keys/save_api_key', $data);

        // Verifica la respuesta
        $response->assertStatus(422)
                ->assertJson(['message' => 'Hay campos incorrectos']);



    }

    /** @test  Elimina Apikey Exitoso*/
    public function testDeleteApiKeySuccessfully()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de la solicitud
        $apikey1 = ApiKeysModel::factory()->create()->first();


        // Verifica que la clave API se haya creado en la base de datos
        $this->assertDatabaseHas('api_keys', ['uid' => $apikey1->uid]);

        $data = [
            'uids' => [$apikey1->uid], // Asegúrate de enviar un array con el UID
        ];

        // Realiza la solicitud DELETE
        $response = $this->deleteJson('/administration/api_keys/delete_api_key', $data);

        // Verifica la respuesta
        $response->assertStatus(200)
                ->assertJson(['message' => 'Claves de API eliminadas correctamente']);

        // Verifica que la clave API se haya eliminado de la base de datos
        $this->assertDatabaseMissing('api_keys', ['uid' => $apikey1->uid]);
    }

    /** @test  Obtener Apikey con paginación*/
    public function testGetApiKeysWithPagination()
    {
        // Crear varias claves API en la base de datos
        ApiKeysModel::factory()->count(2)->create();


        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/api_keys/get_api_keys');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene la estructura esperada
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'name',
                    'api_key',
                    // Agrega otros campos que esperas que estén en el modelo
                ],
            ],
            'last_page',
            'per_page',
            'total',
        ]);
    }

    /** @test  Obtener Apikey medianate búsqueda*/
    public function testSearchApiKeys()
    {

        // Crear claves API en la base de datos
        ApiKeysModel::factory()->create(['name' => 'Searchable API Key', 'api_key' => 'searchable_key']);
        ApiKeysModel::factory()->create(['name' => 'Not Searchable API Key', 'api_key' => 'not_searchable_key']);

        // Hacer una solicitud GET a la ruta con un parámetro de búsqueda
        $response = $this->get('/administration/api_keys/get_api_keys?search=Searchable');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

    }

    /** @test  Obtener orden de Api Keys*/
    public function testSortApiKeys()
    {
        // Crear claves API en la base de datos
        ApiKeysModel::factory()->create(['name' => 'B API Key', 'api_key' => 'b_key']);
        ApiKeysModel::factory()->create(['name' => 'A API Key', 'api_key' => 'a_key']);

        // Hacer una solicitud GET a la ruta con parámetros de ordenación
        $response = $this->get('/administration/api_keys/get_api_keys?sort[0][field]=name&sort[0][dir]=asc&size=10');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que las claves API devueltas están ordenadas correctamente
        $data = $response->json('data');
        $this->assertEquals('A API Key', $data[0]['name']);
        $this->assertEquals('B API Key', $data[1]['name']);
    }

    /** @test  Obtener Api Keys por uid*/
    public function testGetApiKeyByUid()
    {
        // Crear una clave API en la base de datos
        $apiKey = ApiKeysModel::factory()->create()->first();

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/api_keys/get_api_key/' . $apiKey->uid);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene los datos esperados
        $data = $response->json();
        $this->assertEquals($apiKey->uid, $data['uid']);
        $this->assertEquals($apiKey->name, $data['name']);
        $this->assertEquals($apiKey->api_key, $data['api_key']);
    }
}
