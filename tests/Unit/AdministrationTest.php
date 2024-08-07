<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\FooterPagesModel;
use App\Models\HeaderPagesModel;
use App\Models\LicenseTypesModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RedirectionQueriesEducationalProgramTypesModel;



class AdministrationTest extends TestCase {
    use RefreshDatabase;
    /**
     * @group configdesistema
     */

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

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

        // Verificar la respuesta
        $response->assertStatus(200);


        // Limpiar el archivo temporal
        unlink($tempFilePath);
    }


    /**
     * @testdox Verifica habilitación/deshabilitación de valoraciones
     */
    // public function testValoracionesPersistenEnBaseDeDatos() {

    //     $user = UsersModel::factory()->create();
    //     $this->actingAs($user);


    //     $requestData = [
    //         'learning_objects_appraisals' => true,
    //         'operation_by_calls' => false,
    //     ];

    //     $response = $this->post('/administration/save_general_options', $requestData);

    //     $this->assertResponseStatus($response);
    //     $this->assertJsonResponse($response);
    //     $this->assertDatabaseValues();
    // }

    protected function assertResponseStatus($response) {
        $response->assertStatus(200);
    }

    protected function assertJsonResponse($response) {
        $response->assertJson(['message' => 'Opciones guardadas correctamente']);
    }

    protected function assertDatabaseValues() {
        $this->assertTrue(GeneralOptionsModel::where('option_name', 'learning_objects_appraisals')->first()->option_value);
        $this->assertFalse(GeneralOptionsModel::where('option_name', 'operation_by_calls')->first()->option_value);
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

// dd($headerPage1);
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

    public function testCreateFooterPage()
    {
        // Datos de entrada
        $data = [
            'name' => 'Footer Page 1',
            'content' => 'Contenido del pie de página 1',
            'slug' => 'footer-page-1',
            'order' => 1,
            'footer_page_uid' => null,
            'parent_page_uid' => null,
        ];

        // Realiza la solicitud POST
        $response = $this->postJson('/administration/footer_pages/save_footer_page', $data);

        // Verifica la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Página de footer creada correctamente']);

        // Verifica que la página se haya guardado en la base de datos
        $this->assertDatabaseHas('footer_pages', [
            'slug' => 'footer-page-1',
            'name' => 'Footer Page 1',
        ]);
    }

    public function testUpdateFooterPage()
    {
        // Crea una página de pie de página existente
        $footerPageUid= Str::uuid();
        FooterPagesModel::insert([
            'uid' => $footerPageUid,
            'name' => 'Footer Page Original',
            'content' => 'Contenido original del pie de página',
            'slug' => 'footer-page-original',
            'order' => 1,
        ]);

        // Datos de entrada para la actualización
        $data = [
            'footer_page_uid' => $footerPageUid,
            'name' => 'Footer Page Actualizada',
            'content' => 'Contenido actualizado del pie de página',
            'slug' => 'footer-page-actualizada',
            'order' => 2,
            'parent_page_uid' => null,
        ];

        // Realiza la solicitud POST para actualizar
        $response = $this->postJson('/administration/footer_pages/save_footer_page', $data);

        // Verifica la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Página de footer actualizada correctamente']);

        // Verifica que la página se haya actualizado en la base de datos
        $this->assertDatabaseHas('footer_pages', [
            'uid' => $footerPageUid,
            'name' => 'Footer Page Actualizada',
            'content' => 'Contenido actualizado del pie de página',
            'slug' => 'footer-page-actualizada',
            'order' => 2,
        ]);
    }

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

    public function testDeleteNonExistingFooterPages()
    {
        // Datos de entrada para la eliminación de un UID que no existe
        $data = [
            'uids' => ['non-existing-uid'],
        ];

        // Realiza la solicitud DELETE
        $response = $this->deleteJson('/administration/footer_pages/delete_footer_pages', $data);

        // Verifica la respuesta
        $response->assertStatus(200) // Asegúrate de que la respuesta sea 200, ya que el método no lanza un error si no encuentra el UID
                 ->assertJson(['message' => 'Páginas de footer eliminadas correctamente']);
    }

/**
*  @test  Sugerencias y mejoras*/
    public function testSavesEmailSuggestions()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $emailuid = Str::uuid();
        $data = [
            'uid'=> $emailuid,
            'email' => 'test@example.com', // Asegúrate de que sea una cadena
        ];

        // Realiza la solicitud POST a la ruta correcta
        $response = $this->postJson('/administration/suggestions_improvements/save_email', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Email añadido correctamente',
                 ]);

        $this->assertDatabaseHas('suggestion_submission_emails', [
            'email' => 'test@example.com',
        ]);
    }

    public function testInvalidEmailReturnsError()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $data = [
            'email' => 'invalid-email', // Email no válido
        ];

        // Realiza la solicitud POST a la ruta correcta
        $response = $this->postJson('/administration/suggestions_improvements/save_email', $data);

        // Verifica que la respuesta tenga el código de estado 406 y el mensaje esperado
        $response->assertStatus(406)
                 ->assertJson([
                     'message' => 'El email es inválido',
                 ]);
    }


/**
 * @test Redirección de consultas por tipos de programas formativos */
    public function testCreateLicense()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        $data = [
            'uids' => [$license->uid],
            'name' => $license->name,
        ];

        $response = $this->postJson('/administration/licenses/save_license', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencia añadida correctamente']);

        $this->assertDatabaseHas('license_types', [
            'uid' => $license->uid,
            'name' => $license->name
        ]);
    }

    public function testSaveLicenseWithInvalidData()
    {
        $data = [
            'name' => '',
        ];

        $response = $this->postJson('/administration/licenses/save_license', $data);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Algunos campos son incorrectos'])
            ->assertJsonStructure(['errors' => ['name']]);


    }

    public function testSaveLicenseUpdatesExistingLicense()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        $data = [
            'uid' => $license->uid,
            'name' => 'Licencia prueba'
        ];

         $response = $this->postJson('/administration/licenses/save_license', $data);


        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencia añadida correctamente']);

        $this->assertDatabaseHas('license_types', [
             'name' => 'Licencia prueba',
        ]);
    }

    public function testDeleteLicensesSuccessfully()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        $data = [
            'uids' => [$license->uid],
        ];


        // Realiza la solicitud DELETE para eliminar la licencia
        $response = $this->deleteJson('/administration/licenses/delete_licenses', $data);

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencias eliminadas correctamente']);

        // Verifica que la licencia ya no existe en la base de datos
        $this->assertDatabaseMissing('license_types', [
            'uid' => $license->uid,
        ]);
    }

}




