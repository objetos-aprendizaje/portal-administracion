<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use App\Models\LicenseTypesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Jobs\RegenerateAllEmbeddingsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Administration\GeneralAdministrationController;

class AdministrationGeneralTest extends TestCase
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

    public function testIndexViewGeneralAdministrationController()
    {

        // Crear un usuario de prueba y asignar roles
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
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
        $response = $this->get('/administration/general');

        // Asegúrate de que la respuesta sea exitosa
        $response->assertStatus(200);

        // Asegúrate de que se retorne la vista correcta
        $response->assertViewIs('administration.general');

        // Asegúrate de que la vista tenga los datos correctos
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('page_name', 'Configuración general');
        $response->assertViewHas('page_title', 'Configuración general');
        $response->assertViewHas('resources', ['resources/js/administration_module/general.js']);
        $response->assertViewHas('submenuselected', 'administracion-general');
    }

    /** @test Guardar info Universidad Exitoso*/
    public function testSavesUniversityInfo()
    {
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

            // Datos de prueba
            $data = [
                'company_name' => 'Universidad Ejemplo',
                'cif' => 'A12345678',
                'fiscal_domicile' => 'Calle Ejemplo, 123',
                'phone_number' => '123456789',
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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


     /** @test */
     public function testSaveCarrouselWithError422()
     {
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

             // Prepara los datos iniciales
             GeneralOptionsModel::create(['option_name' => 'carrousel_title', 'option_value' => '']);
             GeneralOptionsModel::create(['option_name' => 'carrousel_description', 'option_value' => '']);
             GeneralOptionsModel::create(['option_name' => 'main_slider_color_font', 'option_value' => '']);

             // Datos que se enviarán en la solicitud
             $data = [
                 'carrousel_title' => 'Título del Carrousel',
             ];

             // Realiza la solicitud POST
             $response = $this->postJson('/administration/save_carrousel',$data);

             // Verifica la respuesta
             $response->assertStatus(422)
                 ->assertJson(['message' => 'Algunos campos son incorrectos']);
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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
                'uid' => generateUuid(),
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

    /**
     * @test
     * Este test verifica que la imagen del logo se guarda correctamente y que se actualiza la base de datos.
     */
    public function testSaveLogoImageSuccessfully()
    {
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
            // Simular el almacenamiento
            Storage::fake('local');

            // Crear un archivo de prueba simulado
            $file = UploadedFile::fake()->image('logo.png');

            // Crear un registro en la base de datos para el logo
            GeneralOptionsModel::create([
                'option_name' => 'logo_poa',
                'option_value' => ''
            ]);

            // Simular los datos de la solicitud, incluyendo el archivo correctamente
            $request = Request::create('/administration/save_logo_image', 'POST', [
                'logoId' => 'logo_poa',
            ], [], ['logoPoaFile' => $file]);

            // Instanciar el controlador
            $controller = new GeneralAdministrationController();

            // Ejecutar el método del controlador
            $response = $controller->saveLogoImage($request);

            // Convertir la respuesta en una instancia de TestResponse para utilizar assertStatus
            $response = $this->createTestResponse($response);

            // Verificar que la respuesta sea exitosa
            $response->assertStatus(200);

            // Obtener la estructura de la respuesta JSON
            $response->json();

            // Verificar que la base de datos se actualizó correctamente
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'logo_poa',
            ]);

            // Verificar si se generó un archivo en la ubicación esperada
            $logoOption = GeneralOptionsModel::where('option_name', 'logo_poa')->first();

            if ($logoOption) {
                $this->assertTrue(Storage::disk('local')->exists($logoOption->option_value));
            } else {
                $this->fail('El archivo no se guardó en la base de datos.');
            }
        }
    }

    /**
     * @test
     * Este test verifica que el método saveLogoImage retorne un error si no se sube un archivo.
     */
    public function testSaveLogoImageFailsWithoutFile()
    {
        // Simular los datos de la solicitud sin el archivo
        $request = Request::create('/administration/save_logo_image', 'POST', [
            'logoId' => 'logo_poa',
        ]);

        // Instanciar el controlador
        $controller = new GeneralAdministrationController();

        // Ejecutar el método del controlador
        $response = $controller->saveLogoImage($request);

        // Convertir la respuesta en una instancia de TestResponse para utilizar assertStatus y assertJson
        $response = $this->createTestResponse($response);

        // Verificar que el código de estado es 200, dado que el controlador no lanza un 400
        $response->assertStatus(200);

        // Verificar que la respuesta contiene el mensaje de error esperado
        $response->assertJson([
            'message' => env('ERROR_MESSAGE'),
        ]);
    }

    /**
     * @test
     * Este test verifica que el método saveLogoImage retorne un error si no se puede guardar el archivo.
     */
    public function testSaveLogoImageFailsToSaveFile()
    {
        // Simular el almacenamiento
        Storage::fake('local');

        // Crear un archivo de prueba simulado
        $file = UploadedFile::fake()->image('logo.png');

        // Simular que el almacenamiento falle
        Storage::shouldReceive('putFileAs')->andReturn(false);

        // Simular los datos de la solicitud
        $request = Request::create('/administration/save_logo_image', 'POST', [
            'logoId' => 'logo_poa',
        ], [], ['logoPoaFile' => $file]);

        // Instanciar el controlador
        $controller = new GeneralAdministrationController();

        // Ejecutar el método del controlador
        $response = $controller->saveLogoImage($request);

        // Convertir la respuesta en una instancia de TestResponse para utilizar assertStatus y assertJson
        $response = $this->createTestResponse($response);

        // Verificar la respuesta
        $response->assertStatus(200);
    }


    /** @test *RegenerateAllEmbeddings*/
    public function testRegeneratesEmbeddingsSuccessfully()
    {
        // Configurar datos de prueba
        $options = ['openai_key' => env('OPENAI_KEY')];
        app()->instance('general_options', $options);

        // Simular que no hay un trabajo pendiente
        DB::shouldReceive('table->where->exists')
            ->once()
            ->andReturn(false);

        // Verificar que se dispara el trabajo
        Queue::fake();
        Queue::assertNothingPushed();

        $response = $this->postJson('/administration/regenerate_embeddings');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Embeddings regenerados correctamente']);

        Queue::assertPushed(RegenerateAllEmbeddingsJob::class);
    }

    /** @test */
    public function testFailsIfOpenaiNotConfigured()
    {
        // Simular que no hay clave de OpenAI configurada
        $options = ['openai_key' => null];
        app()->instance('general_options', $options);

        $response = $this->postJson('/administration/regenerate_embeddings');

        $response->assertJson(['message' => 'No se ha configurado la clave de OpenAI']);
    }

    /** @test */
    public function testFailsIfTherePendingJob()
    {
        // Configurar datos de prueba
        $options = ['openai_key' => 'valid_key'];
        app()->instance('general_options', $options);

        // Simular que hay un trabajo pendiente
        DB::shouldReceive('table->where->exists')
            ->once()
            ->andReturn(true);

        $response = $this->postJson('/administration/regenerate_embeddings');

        $response->assertJson(['message' => 'Ya se están regenerando los embeddings. Espere unos minutos.']);
    }

    public function testSavesOpenaiSuccessfully()
    {
        // Datos de entrada
        $data = ['openai_key' => 'sk-12345'];

        // Realizar la solicitud POST al endpoint
        $response = $this->postJson('/administration/save_openai_form', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Clave de OpenAI guardada correctamente']);

        // Verificar que el valor se haya guardado en la base de datos
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'openai_key',
            'option_value' => 'sk-12345',
        ]);
    }

    /** @test */
    public function testSavesGeneralOptionsSuccessfully()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        Auth::login($user);

        // Preparar datos iniciales para la prueba
        GeneralOptionsModel::factory()->create(['option_name' => 'learning_objects_appraisals', 'option_value' => '']);
        GeneralOptionsModel::factory()->create(['option_name' => 'operation_by_calls', 'option_value' => '']);

        // Datos de entrada
        $data = [
            'learning_objects_appraisals' => 'New appraisal value',
            'operation_by_calls' => 'New operation value',
        ];

        // Realizar la solicitud POST al endpoint
        $response = $this->postJson('/administration/save_general_options', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Opciones guardadas correctamente']);

        // Verificar que los valores se hayan actualizado en la base de datos
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'learning_objects_appraisals',
            'option_value' => 'New appraisal value',
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'operation_by_calls',
            'option_value' => 'New operation value',
        ]);
    }
}
