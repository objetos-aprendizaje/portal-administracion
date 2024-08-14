<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use App\Models\FooterPagesModel;
use App\Models\HeaderPagesModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;


class AdministrationConfigSystemTest extends TestCase
{
    /**
 * @group configdesistema
 */
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
    * @testdox Guardar Logo
    */
    public function testSaveLogoImage()
    {
        // Crear un archivo temporal
        $tempFilePath = tempnam(sys_get_temp_dir(), 'logo_');
        file_put_contents($tempFilePath, 'Contenido de prueba');

        // Simular el archivo como un UploadedFile
        $file = new UploadedFile($tempFilePath, 'logo.png', 'image/png', null, true);

        // Simular el almacenamiento
        Storage::fake('local');

        // Realizar la petición POST
        $response = $this->postJson('/administration/save_logo_image', [
            'logoPoaFile' => $file,
            'logoId' => 'logoId',
        ]);

        // Verifica la respuesta
        $response->assertStatus(200);

        // Limpia el archivo temporal
        unlink($tempFilePath);
    }

    /**
    * @testdox ERROR_MESSAGE al guardar el logo
    */

    public function testSaveLogoImageWithoutFile()
    {
        $response = $this->postJson('/administration/save_logo_image', [
            'logoId' => 'test_logo_id'
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => env('ERROR_MESSAGE')]);
    }



    /**
    * @testdox Verifica habilitación/deshabilitación de valoraciones
    */
    public function testValoracionesPersistenEnBaseDeDatos() {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        $requestData = [
            'learning_objects_appraisals' => true,
            'operation_by_calls' => false,
        ];

        $response = $this->post('/administration/save_general_options', $requestData);

        $this->assertResponseStatus($response);
        $this->assertJsonResponse($response);

    }

    protected function assertResponseStatus($response) {
        $response->assertStatus(200);
    }

    protected function assertJsonResponse($response) {
        $response->assertJson(['message' => 'Opciones guardadas correctamente']);
    }

    /**
    * @testdox Solo los admin modifican colores del tema*/
    public function test_admin_can_change_colors() {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            $request = new Request([
                'color1' => '#123456',
                'color2' => '#abcdef',
                'color3' => '#fedcba',
                'color4' => '#654321',
            ]);

            $response = $this->post('/administration/change_colors', $request->all());

            $response->assertStatus(200);
            $response->assertJson(['success' => true, 'message' => 'Colores guardados correctamente']);

            $this->assertDatabaseHas('general_options', ['option_name' => 'color_1', 'option_value' => '#123456']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'color_2', 'option_value' => '#abcdef']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'color_3', 'option_value' => '#fedcba']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'color_4', 'option_value' => '#654321']);
        } else {
            $response = $this->post('/administration/change_colors');
            $response->assertStatus(404);
        }
    }
    /**
    * @testdox Solo los admin configuran pasarela de pago*/
    public function testadminpaymentaccess() {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            $response = $this->get(route('administration-payments'));

            // Verificar que la respuesta contenga la opción de menú "Pagos"
            $response->assertSee('Pagos');
            $response->assertSee('administration-payments'); // Verifica que la clase o el identificador también esté presente

        }
    }

    /**
    * @testdox Usuario no admin sin acceso a configuran pasarela de pago*/
    public function testnonadminpaymentaccess() {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['MANAGEMENT'])) {

            $response = $this->get(route('administration-payments'));

            // Verifica que se devuelve un error 500 (Internal Server Error)
            $response->assertStatus(500);

            // Verifica que la respuesta contenga un mensaje de error específico
            $this->assertStringContainsString('Undefined variable: general_options', $response->getContent());
        }
    }

    /**
    * @testdox permiso a gestores*/
    public function testSaveManagersPermissions()
    {
        // Simular un usuario autenticado
        $this->actingAs(UsersModel::factory()->create());

        // Datos de prueba
        $data = [
            'managers_can_manage_categories' => true,
            'managers_can_manage_course_types' => false,
            'managers_can_manage_educational_resources_types' => true,
            'managers_can_manage_calls' => false,
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/administration/save_manager_permissions', $data);

        // Verificar el estado de la respuesta
        $response->assertStatus(200)
                ->assertJson(['message' => 'Permisos guardados correctamente']);

        // Verificar que los datos se han guardado correctamente en la base de datos
        foreach ($data as $key => $value) {
            $this->assertDatabaseHas('general_options', [
                'option_name' => $key,
                'option_value' => $value,
            ]);
        }

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
            'uids' => ['non-existent-uid-1'],
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
        $headerPageUid= Str::uuid();

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
        // Crea una página de pie de página existente
        $footerPageUid1= Str::uuid();
        FooterPagesModel::insert([
            'uid' => $footerPageUid1,
            'name' => 'Footer Page Original',
            'content' => 'Contenido original del pie de página',
            'slug' => 'footer-page-original',
            'order' => 1,
        ]);

        // Datos de entrada inválidos
        $data = [
            'footer_page_uid' => $footerPageUid1,
            'name' => '',
            'content' => 'Contenido del pie de página',
            'slug' => 'invalid slug', // Slug inválido
            'order' => 'not a number', // Orden no numérico
            'parent_page_uid' => null,
        ];

        // Realiza la solicitud POST para actualizar
        $response = $this->postJson('/administration/footer_pages/save_footer_page', $data);

        // Verifica la respuesta de error
        $response->assertStatus(422)
                 ->assertJson(['message' => 'Hay campos incorrectos']);
    }

/**
 * @test Elimina Footer Page*/
    public function testDeleteFooterPages()
    {
        // Crea algunas páginas de pie de página para eliminar
        $footerPageUid_1= Str::uuid();
        FooterPagesModel::insert([
            'uid' => $footerPageUid_1,
            'name' => 'Footer Page 1',
            'content' => 'Contenido del pie de página 1',
            'slug' => 'footer-page-1',
            'order' => 1,
        ]);

        $footerPageUid_2= Str::uuid();
        FooterPagesModel::insert([
            'uid' => $footerPageUid_2,
            'name' => 'Footer Page 2',
            'content' => 'Contenido del pie de página 2',
            'slug' => 'footer-page-2',
            'order' => 2,
        ]);

        // Datos de entrada para la eliminación
        $data = [
            'uids' => [$footerPageUid_1, $footerPageUid_2],
        ];

        // Realiza la solicitud DELETE
        $response = $this->deleteJson('/administration/footer_pages/delete_footer_pages', $data);

        // Verifica la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Páginas de footer eliminadas correctamente']);

        // Verifica que las páginas se hayan eliminado de la base de datos
        $this->assertDatabaseMissing('footer_pages', [
            'uid' => $footerPageUid_1,
        ]);

        $this->assertDatabaseMissing('footer_pages', [
            'uid' => $footerPageUid_2,
        ]);
    }




}
