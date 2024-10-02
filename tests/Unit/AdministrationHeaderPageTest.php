<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\HeaderPagesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class AdministrationHeaderPageTest extends TestCase
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

    /**
     * @testdox Obtener Index View
     */
    public function testIndexViewHeaderPages()
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


        // Simular la ruta
        $response = $this->get(route('header-pages'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta fue cargada
        $response->assertViewIs('administration.header_pages.index');

        // Verificar que los datos de la vista sean los correctos
        $response->assertViewHas('page_name', 'Páginas de header');
        $response->assertViewHas('page_title', 'Páginas de header');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/header_pages.js",
        ]);
        $response->assertViewHas('tinymce', true);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'header-pages');
    }



    /**
     * @test Crear Header Page*/
    public function testCreateHeaderPage()
    {
        // Datos de prueba para crear una nueva página de encabezado
        $data = [
            'name' => 'Nueva Página de Encabezado',
            'content' => 'Contenido de la nueva página.',
            'slug' => 'nueva-pagina-encabezado',
            'order' => 1,
            'parent_page_uid' => null,
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/administration/header_pages/save_header_page', $data);

        // Verificar el estado de la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Página de header creada correctamente']);

        // Verificar que la página de encabezado se haya guardado en la base de datos
        $this->assertDatabaseHas('header_pages', [
            'slug' => 'nueva-pagina-encabezado',
            'name' => 'Nueva Página de Encabezado',
        ]);
    }

    /**
     * @test Error al eliminar Header Page*/
    public function testDeleteHeaderPagesNotFound()
    {
        // Intentar eliminar páginas que no existen
        $response = $this->deleteJson('/administration/header_pages/delete_header_pages', [
            'uids' => Str::uuid(),
        ]);

        // Verificar el estado de la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Páginas de header eliminadas correctamente']);
    }

    /**
     * @test Elimina Header Page*/
    public function testDeleteHeaderPages()
    {
        // Crear manual páginas de encabezado para eliminar
        $headerPage1Uid = generate_uuid();
        HeaderPagesModel::insert([
            'uid' => $headerPage1Uid,
            'name' => 'Página 1 a Eliminar',
            'content' => 'Contenido de la página 1 a eliminar.',
            'slug' => 'pagina-1-a-eliminar',
            'order' => 1,
            'header_page_uid' => null,
        ]);


        // Realizar la solicitud DELETE
        $response = $this->deleteJson('/administration/header_pages/delete_header_pages', [
            'uids' => [$headerPage1Uid],
        ]);

        // Verificar el estado de la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Páginas de header eliminadas correctamente']);

        // Verificar que las páginas de encabezado ya no existan en la base de datos
        $this->assertDatabaseMissing('header_pages', [
            'uid' => $headerPage1Uid,
        ]);
    }

    /**
     * @test Actualiza Header Page*/
    public function testUpdateHeaderPages()
    {
        // Datos de prueba para crear una nueva página de encabezado
        $data = [
            'name' => 'Nueva Página',
            'content' => 'Contenido nueva página.',
            'slug' => 'nueva-pagina',
            'order' => 1,
            'parent_page_uid' => null,
        ];

        // Realizar la solicitud POST para crear una nueva página
        $response = $this->postJson('/administration/header_pages/save_header_page', $data);

        // Verificar el estado de la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Página de header creada correctamente']);

        // Verificar que la página de encabezado se haya guardado en la base de datos
        $this->assertDatabaseHas('header_pages', [
            'slug' => 'nueva-pagina',
            'name' => 'Nueva Página',
        ]);
    }

    public function testGetHeaderPages()
    {
        $headerPageUid = generate_uuid();

        HeaderPagesModel::insert([
            'uid' => $headerPageUid,
            'name' => 'Página 3',
            'content' => 'Contenido de la página 3.',
            'slug' => 'pagina-3',
            'order' => 3,
            'header_page_uid' => null
        ]);

        // Realizar la solicitud GET para obtener las páginas de encabezado
        $response = $this->getJson('/administration/header_pages/get_header_pages_select');

        // Verificar el estado de la respuesta
        $response->assertStatus(200);

        // Verificar que la respuesta contenga las páginas de encabezado
        $response->assertJsonStructure([
            '*' => [
                'uid',
                'name',
                'content',
                'slug',
                'order',
                'header_page_uid',
                'created_at',
                'updated_at',
            ],
        ]);

        // Verificar que solo se devuelvan las páginas sin parent
        $this->assertCount(1, $response->json());
        $this->assertEquals('Página 3', $response->json()[0]['name']);
    }

    /** @test Obtener Header Pages*/
    public function testGetHeaderPagesWithDefaultSize()
    {
        // Crear datos de prueba
        HeaderPagesModel::factory()->count(3)->create();

        // Realizar la solicitud POST
        $response = $this->postJson('/administration/header_pages/get_header_pages');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'uid',
                        'name',
                        'content',
                        'parent_page_name',
                        // Agrega otros campos que esperas en la respuesta
                    ],
                ],
                'last_page',
                'per_page',
                'total',
            ]);
    }

    /** @test */
    public function testReturnsFilteredHeaderPages()
    {
        // Crear datos de prueba
        HeaderPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Test Page 1',
            'content' => 'Some content'
        ]);
        HeaderPagesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'Another Page', 'content' => 'Different content']);
        HeaderPagesModel::factory()->create(['uid' => generate_uuid(), 'name' => ' Page 2', 'content' => 'More content']);

        // Realizar la solicitud POST con un filtro de búsqueda
        $response = $this->postJson('/administration/header_pages/get_header_pages', [
            'search' => 'Test',
        ]);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Esperamos 1 resultados que coinciden con "Test"
    }


    /** @test Ordenar Header Page */
    public function testReturnsSortedHeaderPages()
    {
        // Crear datos de prueba
        HeaderPagesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'B Page']);
        HeaderPagesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'A Page']);
        HeaderPagesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'C Page']);

        // Realizar la solicitud POST con ordenamiento
        $response = $this->postJson('/administration/header_pages/get_header_pages?sort[0][field]=name&sort[0][dir]=asc&size=5');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'uid',
                        'order',
                        'name',
                        'content',
                    ],
                ],
                'last_page',
                'per_page',
                'total',
            ]);

        // Verificar el orden de los resultados
        $data = $response->json('data');

        $this->assertEquals('A Page', $data[0]['name']);
        $this->assertEquals('B Page', $data[1]['name']);
        $this->assertEquals('C Page', $data[2]['name']);
    }


    /** @test */
    public function testGetReturnsHeaderPage()
    {
        // Crea un encabezado de página en la base de datos
        $headerPage = HeaderPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Test Header Page',
            'content' => 'Contenido',
            'order' => 2
        ])->latest()->first();

        // Realiza una solicitud GET a la ruta que invoca el método getHeaderPage
        $response = $this->getJson('/administration/header_pages/get_header_page/' . $headerPage->uid);


        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que la respuesta contenga los datos correctos
        $response->assertJson([
            'uid' => $headerPage->uid,
            'name' => 'Test Header Page',
            'content' => 'Contenido',
            'order' => 2

        ]);
    }
}
