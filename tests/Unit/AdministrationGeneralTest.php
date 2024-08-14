<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use App\Models\LicenseTypesModel;
use Illuminate\Http\UploadedFile;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Administration\PaymentsController;
use App\Http\Controllers\Administration\GeneralAdministrationController;

class AdministrationGeneralTest extends TestCase
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
    /** @test Guardar info Universidad Exitoso*/
    public function testSavesUniversityInfo()
    {
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

            // Datos de prueba
            $data = [
                'company_name' => 'Universidad Ejemplo',
                'commercial_name' => 'UniEjemplo',
                'cif' => 'A12345678',
                'fiscal_domicile' => 'Calle Ejemplo, 123',
                'work_center_address' => 'Avenida Ejemplo, 456',
            ];

            // Realiza la solicitud POST para guardar la información de la universidad
            $response = $this->postJson('/administration/save_university_info', $data);

            // Verifica que la respuesta sea exitosa
            $response->assertStatus(200);
            $response->assertJson(['message' => 'Datos guardados correctamente']);

            // Verifica que los datos se hayan guardado en la base de datos
            foreach ($data as $key => $value) {
                $this->assertDatabaseHas('general_options', [
                    'option_name' => $key,
                    'option_value' => $value,
                ]);
            }

        }

    }


/** @test */
    public function testSaveScripts()
    {
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
            // Prepara los datos iniciales
            GeneralOptionsModel::create(['option_name' => 'scripts', 'option_value' => '']);

            // Datos que se enviarán en la solicitud
            $data = [
                'scripts' => '<script>alert("Hola Mundo");</script>',
            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/administration/save_scripts', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Scripts guardados correctamente']);

            // Verifica que el valor se haya actualizado en la base de datos
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'scripts',
                'option_value' => '<script>alert("Hola Mundo");</script>',
            ]);


        }
    }

/** @test */
    public function test_save_rrss()
    {
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

            // Prepara los datos iniciales
            GeneralOptionsModel::create(['option_name' => 'facebook_url', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'x_url', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'youtube_url', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'instagram_url', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'telegram_url', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'linkedin_url', 'option_value' => '']);

            // Datos que se enviarán en la solicitud
            $data = [
                'facebook_url' => 'https://facebook.com/example',
                'x_url' => 'https://x.com/example',
                'youtube_url' => 'https://youtube.com/example',
                'instagram_url' => 'https://instagram.com/example',
                'telegram_url' => 'https://telegram.com/example',
                'linkedin_url' => 'https://linkedin.com/example',
            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/administration/save_rrss', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Redes sociales guardadas correctamente']);

            // Verifica que los valores se hayan actualizado en la base de datos
            foreach ($data as $key => $value) {
                $this->assertDatabaseHas('general_options', [
                    'option_name' => $key,
                    'option_value' => $value,
                ]);
            }

        }
    }


/** @test */
    public function testSaveCarrousel()
    {
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

            // Prepara los datos iniciales
            GeneralOptionsModel::create(['option_name' => 'carrousel_title', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'carrousel_description', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'main_slider_color_font', 'option_value' => '']);

            // Datos que se enviarán en la solicitud
            $data = [
                'carrousel_title' => 'Título del Carrousel',
                'carrousel_description' => 'Descripción del Carrousel',
                'main_slider_color_font' => '#ffffff',
            ];

            // Simula un archivo de imagen
            $file = UploadedFile::fake()->image('carrousel-image.jpg');

            // Mockea el método saveFile para que no haga nada
            $this->partialMock(GeneralAdministrationController::class, function ($mock) {
                $mock->shouldReceive('saveFile')->andReturn('images/carrousel-default-images/image.jpg');
            });

            // Realiza la solicitud POST
            $response = $this->postJson('/administration/save_carrousel', array_merge($data, ['carrousel_image_input_file' => $file]));

            // Verifica la respuesta
            $response->assertStatus(200)
                     ->assertJson(['message' => 'Opciones de carrousel guardadas correctamente']);

            // Verifica que los valores se hayan actualizado en la base de datos
            foreach ($data as $key => $value) {
                $this->assertDatabaseHas('general_options', [
                    'option_name' => $key,
                    'option_value' => $value,
                ]);
            }

            // Verifica que la ruta de la imagen se haya guardado correctamente
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'carrousel_image_path',
            ]);

        }
    }

/**@group add_font */
/** @test */
    public function testAddFont()
    {
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

            // Prepara los datos iniciales
            GeneralOptionsModel::create(['option_name' => 'font_key', 'option_value' => '']);

            // Simula un archivo de fuente
            $file = UploadedFile::fake()->create('font.ttf', 100, 'application/octet-stream');

            // Datos que se enviarán en la solicitud
            $data = [
                'fontFile' => $file,
                'fontKey' => 'font_key',
            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/administration/add_font', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                    ->assertJsonStructure(['fontPath', 'message']);

            // Verifica que el valor se haya actualizado en la base de datos
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'font_key',
            ]);

            // Obtiene el valor de font_key para verificar su contenido
            $fontPath = GeneralOptionsModel::where('option_name', 'font_key')->first()->option_value;

            // Verifica que la ruta de la fuente contenga la parte esperada
            $this->assertStringContainsString('fonts/', $fontPath);
            $this->assertStringEndsWith('.ttf', $fontPath); // Verifica que termine con .ttf


        }
    }

/** @test */
    public function testAddFontError()
    {
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
            GeneralOptionsModel::create(['option_name' => 'font_key', 'option_value' => '']);

            // Simula un archivo de fuente
            $file = UploadedFile::fake()->create('font.ttf', 100, 'application/octet-stream');

            // Datos que se enviarán en la solicitud
            $data = [
                'fontFile' => $file,
                'fontKey' => 'font_key',
            ];

            $response = $this->getJson('/administration/add_font', $data);

            // Verifica que se genere un error 405
            $response->assertStatus(405);
        }
    }

    /** @test */
    public function testDeleteFont()
    {
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
            // Prepara los datos iniciales
            $fontKey = 'font_key';
            $fontPath = 'fonts/font.ttf';
            GeneralOptionsModel::create(['option_name' => $fontKey, 'option_value' => $fontPath]);

            // Datos que se enviarán en la solicitud
            $data = [
                'fontKey' => $fontKey,
            ];

            // Realiza la solicitud DELETE
            $response = $this->deleteJson('/administration/delete_font', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                    ->assertJsonStructure(['message']);

            // Verifica que el valor se haya actualizado en la base de datos
            $this->assertDatabaseHas('general_options', [
                'option_name' => $fontKey,
                'option_value' => null,
            ]);

            // Verifica que el archivo se haya eliminado
            $this->assertFileDoesNotExist(storage_path($fontPath));
        }
    }

/**@group Apikey */
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

/* Group Smtp*/

/** @test */
    public function testSmtpValidatesRequiredFields()
    {
        $response = $this->postJson('/administration/save_smtp_email_form', []);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Algunos campos son incorrectos',
            'errors' => [
                'smtp_server' => ['El servidor de correo es obligatorio'],
                'smtp_port' => ['El puerto del servidor de correo es obligatorio'],
                'smtp_user' => ['El usuario del servidor de correo es obligatorio'],
                'smtp_name_from' => ['El nombre del servidor de correo es obligatorio'],
                'smtp_address_from' => ['La dirección del servidor de correo es obligatoria'],
                'smtp_password' => ['La contraseña del servidor de correo es obligatoria'],
            ],
        ]);
    }

    /** @test  Actualiza SMTP*/
    public function testSmtpUpdateEmailServerConfiguration()
    {

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
            // Prepara los datos iniciales
            GeneralOptionsModel::create(['option_name' => 'smtp_host', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'smtp_port', 'option_value' => '']);
            GeneralOptionsModel::create(['option_name' => 'smtp_user', 'option_value' => '']);

            // Datos que se enviarán en la solicitud
            $data = [
                'smtp_server' => 'server',
                'smtp_name_from' => 'name_from',
                'smtp_host' => 'smtp.example.com',
                'smtp_port' => '587',
                'smtp_user' => 'user@example.com',
                'smtp_address_from' => 'http://prueba.com',
                'smtp_password' => 'secure_password', // Simulando que no se proporciona la contraseña
            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/administration/save_smtp_email_form', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Servidor de correo guardado correctamente']);

            // Verifica que los valores se hayan actualizado en la base de datos
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'smtp_host',
                'option_value' => 'smtp.example.com',
            ]);

            $this->assertDatabaseHas('general_options', [
                'option_name' => 'smtp_port',
                'option_value' => '587',
            ]);

            $this->assertDatabaseHas('general_options', [
                'option_name' => 'smtp_user',
                'option_value' => 'user@example.com',
            ]);

            // Verifica que la contraseña SMTP se haya actualizado
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'smtp_password',
                'option_value' => 'secure_password',

            ]);

            // Verifica que los parámetros se hayan almacenado en caché
            $this->assertEquals($data, Cache::get('parameters_email_service'));
        }
    }


    /** @test  Restaura el Logo*/
    public function testRestoresLogoImage()
    {

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
            // Configura un logo en la base de datos para probar la restauración
            $logoId = 'logo_example';
            GeneralOptionsModel::create([
                'option_name' => $logoId,
                'option_value' => '/images/custom-logos/logo.png',
            ]);

            // Realiza la solicitud POST para restaurar el logo
            $response = $this->postJson('/administration/restore_logo_image', ['logoId' => $logoId]);

            // Verifica que la respuesta sea exitosa
            $response->assertStatus(200);
            $response->assertJson(['message' => 'Logo eliminado correctamente']);

            // Verifica que el logo se haya eliminado de la base de datos
            $this->assertDatabaseMissing('general_options', [
                'option_name' => $logoId,
                'option_value' => '/images/custom-logos/logo.png',
            ]);

        }
    }

/** Group Certificate Digital*/
/**
* @test  Guarda CertidigitalConfiguration */

    public function testSaveCertidigitalForm()
        {
            // Simular el inicio de sesión del usuario

            $user = UsersModel::factory()->create();
            $this->actingAs($user);


            // Datos de prueba para el formulario
            $formData = [
                'certidigital_url' => 'https://example.com',
                'certidigital_client_id' => 'client_id',
                'certidigital_client_secret' => 'client_secret',
                'certidigital_username' => 'username',
                'certidigital_password' => 'password',
            ];

            // Hacer una solicitud POST a la ruta
            $response = $this->post('/administration/certidigital/save_certidigital_form', $formData);

            // Comprobar que la respuesta es correcta
            $response->assertStatus(200);

            // Comprobar que la respuesta contiene el mensaje de éxito
            $response->assertJson(['message' => 'Configuración de certidigital correctamente']);

            // Comprobar que los datos se hayan guardado correctamente en la base de datos
            $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_url', 'option_value' => 'https://example.com']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_client_id', 'option_value' => 'client_id']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_client_secret', 'option_value' => 'client_secret']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_username', 'option_value' => 'username']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'certidigital_password', 'option_value' => 'password']);
    }




}


