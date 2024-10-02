<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\FooterPagesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Exceptions\OperationFailedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdministrationFooterPageTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }
    public function testIndexViewFooterPages()
    {
        // Crear un usuario de prueba y asignar roles
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
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
        $response = $this->get(route('footer-pages'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta fue cargada
        $response->assertViewIs('administration.footer_pages.index');

        // Verificar que los datos de la vista sean los correctos
        $response->assertViewHas('page_name', 'Páginas de footer');
        $response->assertViewHas('page_title', 'Páginas de footer');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/footer_pages.js",
        ]);
        $response->assertViewHas('tinymce', true);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'footer-pages');
        $response->assertViewHas('pages');
    }


    public function testSaveFooterPages()
    {

        // Crear una opción general para probar la actualización
        GeneralOptionsModel::create([
            'option_name' => 'legalAdvice',
            'option_value' => 'Texto legal',

        ]);

        // Realizar la solicitud POST para guardar las opciones
        $response = $this->postJson('/administration/footer_pages/save_footer_page', [
            'legalAdvice' => 'Texto legal actualizado',
            'name' => 'nombre footer',
            'slug' => 'texto-legal',
            'content' => 'content',
            'acceptance_required' => 0
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Página de footer creada correctamente']);

    }

    /** @test Obtener Footer page */
    public function testGetFooterPagesWithDefaultSize()
    {
        // Crear un nuevo usuario
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Crea algunos registros de FooterPagesModel
        FooterPagesModel::factory()->create()->latest()->first();

        // Realiza la solicitud POST
        $response = $this->postJson('/administration/footer_pages/get_footer_pages');

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }


    /** @test Obtener Footer page con opción búsqueda*/
    public function testGetFooterPagesWithSearch()
    {
        // Crear un nuevo usuario
        $user = UsersModel::factory()->create();
        Auth::login($user);
        // Arrange: Crea registros de FooterPagesModel
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'About Us']);
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Contact']);
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Privacy Policy']);

        // Act: Realiza la solicitud POST con un parámetro de búsqueda
        $response = $this->postJson('/administration/footer_pages/get_footer_pages', [
            'search' => 'About'
        ]);

        // Assert: Verifica que solo se devuelva el registro que coincide
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('About Us', $response->json('data.0.name'));
    }

    /** @test */
    public function testGetFooterPagesWithSort()
    {

        // Crear un nuevo usuario
        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Arrange: Crea registros de FooterPagesModel
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Contact'])->latest()->first();
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'About Us'])->latest()->first();
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Privacy Policy'])->latest()->first();

        // Realiza la solicitud POST con parámetros de ordenamiento
        $response = $this->postJson('/administration/footer_pages/get_footer_pages?sort[0][field]=name&sort[0][dir]=asc&size=10');

        // Verifica que los registros se devuelvan en el orden correcto
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertEquals('About Us', $data[0]['name']);
        $this->assertEquals('Contact', $data[1]['name']);
        $this->assertEquals('Privacy Policy', $data[2]['name']);
    }

    /** @test Obtener Footer Page por uid*/
    public function testGetAFooterPageByUid()
    {

        $user = UsersModel::factory()->create();
        Auth::login($user);

        // Arrange: Crea un registro de FooterPagesModel
        $footerPage = FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'About Us',
            'content' => 'This is the About Us page.'
        ])->first();

        // Act: Realiza la solicitud GET
        $response = $this->getJson('/administration/footer_pages/get_footer_page/'.$footerPage->uid);

        // Assert: Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson([
            'uid' => $footerPage->uid,
            'name' => 'About Us',
            'content' => 'This is the About Us page.'
        ]);
    }

    /** @test Elimina Footer Page*/
    public function testDeleteFooterPages()
    {
        // Arrange: Crea algunos registros de FooterPagesModel
        $footer1 = FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Contact'])->latest()->first();
        $footer2 = FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'About Us'])->latest()->first();
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Privacy Policy'])->latest()->first();

        // Act: Realiza la solicitud DELETE
        $response = $this->deleteJson('/administration/footer_pages/delete_footer_pages', [
            'uids' => [$footer1->uid, $footer2->uid] // Envía los UIDs de las páginas a eliminar
        ]);

        // Assert: Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Páginas de footer eliminadas correctamente']);

        // Verifica que las páginas hayan sido eliminadas
        $this->assertDatabaseMissing('footer_pages', ['uid' =>$footer1->uid]);
        $this->assertDatabaseMissing('footer_pages', ['uid' =>$footer2->uid]);
    }


       /**
    * @test Crear Footer Pages Error*/
    public function testErrorSaveFooterPages()
    {
        // Simular un usuario autenticado
        $this->actingAs(UsersModel::factory()->create());

        // Datos de prueba
        $legalAdvice = 'Este es un consejo legal';

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/footer_pages', [
            'legalAdvice' => $legalAdvice
        ]);

        // Verificar la respuesta
        $response->assertStatus(405);


    }

/**
 * @test Actualiza Footer Page Error*/
    public function testUpdateFooterPageWithValidationErrors()
    {

        // Simular un usuario autenticado
        $this->actingAs(UsersModel::factory()->create());

        // Crea una página de pie de página existente
        $footerPageUid1= generate_uuid();
        FooterPagesModel::factory()->create([
            'uid' => $footerPageUid1,
            'name' => 'Footer Page Original',
            'content' => 'Contenido original del pie de página',
            'slug' => 'footer-page-original',
        ])->latest()->first();

        // Datos de entrada inválidos
        $data = [
            'name' => '',
            'content' => 'Contenido del pie de página',
            'slug' => 'invalid slug', // Slug inválido
            'parent_page_uid' => null,
        ];

        // Realiza la solicitud POST para actualizar
        $response = $this->postJson('/administration/footer_pages/save_footer_page', $data);

        // Verifica la respuesta de error
        $response->assertStatus(422)
                 ->assertJson(['message' => 'Hay campos incorrectos']);
    }

    public function testSlugAlreadyExistsInFooterPages()
    {
            // Crear una página de footer existente
        FooterPagesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Página de Footer Existente',
            'content' => 'Contenido existente',
            'slug' => 'pagina-existente',
            'version' => '1.0',
            'acceptance_required' => false,
        ])->first();

        // Preparar datos de prueba que causen conflicto con el slug existente
        $requestData = [
            'slug' => 'pagina-existente', // Slug que ya existe
            'name' => 'Nueva Página de Footer',
            'content' => 'Contenido de la nueva página de footer',
            'version' => '1.0',
            'acceptance_required' => false,
        ];

        // Ejecutar la llamada al método usando la ruta correcta
        $response = $this->postJson('/administration/footer_pages/save_footer_page', $requestData);

        // Verificar que se haya lanzado la excepción
        $this->assertNotNull($response->exception);
        $this->assertInstanceOf(OperationFailedException::class, $response->exception);
        $this->assertEquals("El slug intriducido ya existe", $response->exception->getMessage());

        // Verificar que no se haya guardado nada en la base de datos
        $this->assertDatabaseMissing('footer_pages', [
            'slug' => 'pagina-existente',
            'name' => 'Nueva Página de Footer',
        ]);
    }

    public function testUpdateFooterPageWithExistingSlug()
    {
    // Crear una página de footer existente
    $existingFooterPage = FooterPagesModel::factory()->create([
        'uid' => generate_uuid(),
        'name' => 'Página de Footer Existente',
        'content' => 'Contenido existente',
        'slug' => 'pagina-existente',
        'version' => '1.0',
        'acceptance_required' => false,
    ])->first();

    // Crear otra página de footer con el mismo slug
    FooterPagesModel::factory()->create([
        'uid' => generate_uuid(),
        'name' => 'Otra Página de Footer',
        'content' => 'Contenido de otra página',
        'slug' => 'pagina-existente', // Este slug ya existe
        'version' => '1.0',
        'acceptance_required' => false,
    ]);

    // Preparar datos de prueba para la actualización
    $requestData = [
        'name' => 'Página de Footer Actualizada',
        'content' => 'Contenido actualizado',
        'slug' => 'pagina-existente', // Este slug ya existe, debe causar un conflicto
        'version' => '1.1',
        'acceptance_required' => false,
    ];

    // Ejecutar la llamada al método usando la ruta correcta
    $response = $this->postJson('/administration/footer_pages/save_footer_page', $requestData);

    // Verificar que la respuesta tenga el código de estado 406 (Not Acceptable)
    $response->assertStatus(406);
    $response->assertJson(['message' => 'El slug intriducido ya existe']);

    // Verificar que no se haya actualizado nada en la base de datos
    $existingFooterPage->refresh(); // Refrescar la instancia para obtener los datos más recientes
    $this->assertEquals('Página de Footer Existente', $existingFooterPage->name);
    $this->assertEquals('Contenido existente', $existingFooterPage->content);
    $this->assertEquals('pagina-existente', $existingFooterPage->slug);
    }
}




