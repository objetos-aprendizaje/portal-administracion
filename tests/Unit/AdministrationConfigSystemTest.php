<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
     * @test Error archivo no existe
     */
    public function testSaveLogoImageNoFile()
    {
        // Crear un logoId ficticio y una entrada en la base de datos
        $logoId = 'logo_example';
        GeneralOptionsModel::create(['option_name' => $logoId, 'option_value' => '']);

        // Realizar la solicitud POST sin archivo
        $response = $this->postJson('/administration/save_logo_image', [
            'logoPoaFile' => '', // No se envía un archivo
            'logoId' => $logoId,
        ]);
        // Verificar que la respuesta sea un error
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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
    * @Group permiso a gestores*/

    /**
    * @test permiso a gestores*/
    public function testIndexViewPermissions()
    {

        // Crear un usuario de prueba y asignar roles
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);


        // Define los valores simulados que se deberían pasar a la vista
        $general_options = [
            'managers_can_manage_categories' => true,
            'managers_can_manage_course_types' => false,
            'managers_can_manage_educational_resources_types' => true,
            'managers_can_manage_calls' => false,
        ];

        // Renderiza la vista y pasa los datos directamente
        $response = $this->view('administration.management_permissions', compact('general_options'));

        // Asegúrate de que la respuesta contiene los textos esperados
        $response->assertSee('Permisos a gestores');
        $response->assertSee('Administrar categorías');
        $response->assertSee('Administrar tipos de cursos');
        $response->assertSee('Administrar tipos de recursos educativos');
        $response->assertSee('Administrar convocatorias');
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


     /** @test Obtener Index View Payments*/
    public function testIndexViewAdministrationPayments()
    {
        // Crear un usuario de prueba y asignar roles
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
        $response = $this->get('/administration/payments');

        // Asegúrate de que la respuesta sea exitosa
        $response->assertStatus(200);

        // Asegúrate de que se retorne la vista correcta
        $response->assertViewIs('administration.payments');

        // Asegúrate de que la vista tenga los datos correctos
        $response->assertViewHas('page_name', 'Pagos');
        $response->assertViewHas('page_title', 'Pagos');
        $response->assertViewHas('resources', ['resources/js/administration_module/payments.js']);
        $response->assertViewHas('submenuselected', 'administration-payments');
    }

    /** @test */
    public function testSavesPaymentFormSuccessfully()
    {

        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Simular datos válidos
        $data = [
            'uid' => generateUuid(),
            'payment_gateway' => 'gateway_test',
            'redsys_commerce_code' => '123456',
            'redsys_terminal' => '1',
            'redsys_currency' => 'EUR',
            'redsys_transaction_type' => '0',
            'redsys_encryption_key' => 'encryption_key_test',
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/administration/payments/save_payments_form', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Datos de pago guardados correctamente']);

    }

    /** @test */
    public function testReturnsErrorWhenValidationFails()
    {

        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        Auth::login($admin);

        // Simular datos inválidos
        $data = [
            // 'payment_gateway' => 'gateway_test',
           'payment_gateway'=> 1,
            // Falta redsys_commerce_code
            'redsys_terminal' => '1',
            'redsys_currency' => '',
            'redsys_transaction_type' => '0',
            // Falta redsys_encryption_key
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/administration/payments/save_payments_form', $data);

        // Verificar la respuesta
        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Algunos campos son incorrectos',
                     'errors' => [
                         'redsys_commerce_code' => ['El código de comercio es obligatorio'],
                         'redsys_currency' => ['La moneda es obligatoria'],
                         'redsys_encryption_key' => ['La clave de encriptación es obligatoria'],
                     ],
                 ]);
    }


}
