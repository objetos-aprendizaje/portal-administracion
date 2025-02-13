<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\LicenseTypesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use App\Models\GeneralOptionsModel;
use App\Services\EmbeddingsService;
use Illuminate\Support\Facades\App;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\CompetenceFrameworksModel;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourcesTagsModel;
use App\Models\EducationalResourceTypesModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\CompetenceFrameworksLevelsModel;
use App\Models\EducationalResourceStatusesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\EducationalResourcesEmbeddingsModel;
use App\Models\EducationalResourcesEmailContactsModel;

class EducationalResourcesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    } // Configuración inicial si es necesario


    /** @test Obtener Index View Recursos Educacionales */

    public function testIndexEducationalResourcesPage()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        app()->instance('general_options', $general_options);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);


        // Crear datos de prueba
        $competenceFramework = CompetenceFrameworksModel::factory()->create([
            'has_levels' => true,
        ]);

        EducationalResourceTypesModel::factory()->count(3)->create();
        CategoriesModel::factory()->count(2)->create();
        LicenseTypesModel::factory()->count(2)->create();
        $competencia = CompetencesModel::factory()->create([
            'competence_framework_uid' => $competenceFramework->uid
        ]);

        LearningResultsModel::factory()->create([
            'competence_uid' => $competencia->uid
        ]);

        CompetenceFrameworksLevelsModel::factory()->create([
            'competence_framework_uid' => $competenceFramework->uid
        ]);

        // Realizar la solicitud GET a la ruta
        $response = $this->get(route('learning-objects-educational-resources'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta se está utilizando
        $response->assertViewIs('learning_objects.educational_resources.index');

        // Verificar que los datos necesarios están presentes en la vista
        $response->assertViewHas('educational_resources_types');
        $response->assertViewHas('categories');
        $response->assertViewHas('license_types');
    }

    /** @test Obtener recursos sin filtros ni ordenamiento */
    public function testGetResourcesWithoutFiltersOrSorting()
    {
        // Creamos un usuario con rol TEACHER

        $user_teacher = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $user_teacher->uid,
                'user_role_uid' => $rol_uid
            ];
        }
        $user_teacher->roles()->sync($roles_to_sync);

        $this->actingAs($user_teacher);

        // Creamos algunos recursos educativos
        EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()->count(15)->create([
                'creator_user_uid' => $user_teacher->uid,
            ]);


        // Llamamos al endpoint
        $response = $this->getJson('/learning_objects/educational_resources/get_resources');

        // Verificamos que la respuesta sea exitosa
        $response->assertStatus(200);


        // Verificamos que los datos devueltos sean correctos
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uid',
                    'title',
                    'description',
                    'status_uid',
                    'status' => [
                        'uid'
                    ],
                    'type' => [
                        'uid'
                    ],
                    'categories' => []

                ]
            ],
            'links',
        ]);
    }

    /** @test Obtener recursos con filtros */
    public function testGetResourcesWithFilters()
    {

        $user_teacher = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $user_teacher->uid,
                'user_role_uid' => $rol_uid
            ];
        }
        $user_teacher->roles()->sync($roles_to_sync);


        $category = CategoriesModel::factory()->create()->first();
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->create([
                'creator_user_uid' => $user_teacher->uid,
            ])->first();

        $resource->categories()->attach($category->uid, [
            'uid' => generateUuid() // Asegúrate de generar un UUID para el campo `uid`
        ]);

        $this->actingAs($user_teacher);

        $filters = [
            'filters' => [
                ['database_field' => 'categories', 'value' => [$category->uid]],
                ['database_field' => 'title', 'value' => 'Titulo'],
                ['database_field' => 'embeddings', 'value' => 0],
            ],
        ];

        $response = $this->getJson('/learning_objects/educational_resources/get_resources?' . http_build_query($filters));

        $response->assertStatus(200);
        
    }

    /** @test Obtener recursos con ordenamiento */
    public function testGetResourcesWithSorting()
    {
        $user_teacher = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $user_teacher->uid,
                'user_role_uid' => $rol_uid
            ];
        }
        $user_teacher->roles()->sync($roles_to_sync);

        EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->create([
                'creator_user_uid' => $user_teacher->uid,
                'title' => 'B Title'
            ]);
        EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->create([
                'creator_user_uid' => $user_teacher->uid,
                'title' => 'A Title'
            ]);

        $this->actingAs($user_teacher);
      

        $response = $this->getJson('/learning_objects/educational_resources/get_resources?sort[0][field]=title&sort[0][dir]=asc&size=2');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('A Title', $data[0]['title']);
        $this->assertEquals('B Title', $data[1]['title']);
    }

    /** @test Obtener recursos con búsqueda */
    public function testGetResourcesWithSearch()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'TEACHER')->first();

        $user->roles()->attach($role->uid, [
            'uid' => generateUuid(),
        ]);

        EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->create([
                'creator_user_uid' => $user->uid,
                'title' => 'Unique Title'
            ]);

        $this->actingAs($user);

        $response = $this->getJson('/learning_objects/educational_resources/get_resources?search=Unique');

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Unique Title']);
    }

    /** @test  testReturns200AndResourceDataIfResourceExists */
    public function testReturns200AndResourceDataIfResourceExists()
    {
        // Crea un recurso educativo simulado en la base de datos
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create()->first();

        // Realiza la solicitud GET a la ruta con un UID válido
        $response = $this->get('/learning_objects/educational_resources/get_resource/' . $resource->uid);

        // Verifica que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verifica que los datos del recurso sean los esperados
        $response->assertJson([
            'uid' => $resource->uid,
            // Puedes agregar otras verificaciones de campos según lo que devuelva el recurso
        ]);

        // Realiza la solicitud GET a la ruta con un UID válido pero no existente error 406
        $response = $this->get('/learning_objects/educational_resources/get_resource/' . generateUuid());

        // Verifica que la respuesta sea 200 (OK)
        $response->assertStatus(406);
        $response->assertJson(['message' => 'El recurso no existe']);
    }

    /** @test Valida que la creación de un recurso falle si los datos no son válidos */
    public function testFailsToCreateResourceIfValidationFails()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Realiza la solicitud POST con datos inválidos
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', [
            'learning_results' => json_encode([10]),
        ]);

        // Verifica que la respuesta sea 422 y contenga errores de validación
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Algunos campos son incorrectos']);


        // Errores faltantes para revisar en la validaciones

        $data = [
            'learning_results' => json_encode([10]),
            'metadata' => json_encode([
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                ],
            ])
        ];

        // Realiza la solicitud POST con datos inválidos
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', $data);

        // Verifica que la respuesta sea 422 y contenga errores de validación
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Algunos campos son incorrectos']);
    }

    /** @test Puedo crear un nuevo recurso educativo correctamente */
    public function testCanCreateANewEducationalResource()
    {
        // Simula el almacenamiento de archivos
        Storage::fake('public');

        $user = UserSmodel::factory()->create();

        $this->actingAs($user);

        AutomaticNotificationTypesModel::factory()->create(
            [
                'code'=> 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS'
            ]
        );

        // Simula un archivo de imagen y un archivo de recurso
        $resourceImage = UploadedFile::fake()->image('resource.jpg');
        $resourceFile = UploadedFile::fake()->create('resource.pdf', 100);

        // Crea algunas categorías y resultados de aprendizaje para asociar con el recurso
        $category1 = CategoriesModel::factory()->create()->first();
        $category2 = CategoriesModel::factory()->create()->first();
        $learningResult1 = LearningResultsModel::factory()->withCompetence()->create();
        $learningResult2 = LearningResultsModel::factory()->withCompetence()->create();

        $educationalResourceTypes = EducationalResourceTypesModel::factory()->create()->first();
        $licenseTypes = LicenseTypesModel::factory()->create()->first();

        // Configura el mock de general_options con la clave correcta
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'necessary_approval_resources' => false, // Corrige el nombre de la clave aquí
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 150, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);


        // Prepara los datos para crear un nuevo recurso
        $data = [
            'title' => 'Nuevo Recurso Educativo',
            'description' => 'Descripción del recurso',
            'educational_resource_type_uid' => $educationalResourceTypes->uid,
            'license_type_uid' => $licenseTypes->uid,
            'resource_way' => 'PDF',
            'resource_image_input_file' => $resourceImage,
            'resource_input_file' => $resourceFile,
            'action' => 'submit',
            'tags' => json_encode(['tag1', 'tag2']),
            'metadata' => json_encode([
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ],
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ]
            ]),
            'categories' => json_encode([$category1->uid, $category2->uid]),
            'contact_emails' => json_encode(['email1@example.com', 'email2@example.com']),
            'learning_results' => json_encode([$learningResult1->uid, $learningResult2->uid]),
            // Otros campos necesarios para la creación
        ];

        // Realiza la solicitud POST para crear un nuevo recurso
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', $data);

        // Verifica que la respuesta sea 200 y el recurso se haya creado correctamente
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Recurso añadido correctamente']);

        // Verifica que el recurso exista en la base de datos
        $this->assertDatabaseHas('educational_resources', [
            'title' => 'Nuevo Recurso Educativo',
            'license_type_uid' => $licenseTypes->uid,
        ]);

        // Verifica que las etiquetas se hayan guardado correctamente
        $this->assertDatabaseHas('educational_resources_tags', ['tag' => 'tag1']);
        $this->assertDatabaseHas('educational_resources_tags', ['tag' => 'tag2']);

        // Verifica que los metadatos se hayan guardado correctamente
        $this->assertDatabaseHas('educational_resources_metadata', ['metadata_key' => 'Nombre del Meta', 'metadata_value' => 'Valor del Meta']);

        // Verifica que las categorías se hayan asociado correctamente
        $this->assertDatabaseHas('educational_resource_categories', ['category_uid' => $category1->uid]);
        $this->assertDatabaseHas('educational_resource_categories', ['category_uid' => $category2->uid]);

        // Verifica que los correos de contacto se hayan guardado correctamente
        $this->assertDatabaseHas('educational_resources_email_contacts', ['email' => 'email1@example.com']);
        $this->assertDatabaseHas('educational_resources_email_contacts', ['email' => 'email2@example.com']);

        // Verifica que los resultados de aprendizaje se hayan asociado correctamente
        $this->assertDatabaseHas('educational_resources_learning_results', ['learning_result_uid' => $learningResult1->uid]);
        $this->assertDatabaseHas('educational_resources_learning_results', ['learning_result_uid' => $learningResult2->uid]);

        // Envia data en modo de Draft

        // Prepara los datos para crear un nuevo recurso
        $data = [
            'title' => 'Nuevo Recurso Educativo',
            'description' => 'Descripción del recurso',
            'educational_resource_type_uid' => $educationalResourceTypes->uid,
            'license_type_uid' => $licenseTypes->uid,
            'resource_way' => 'PDF',
            // 'resource_image_input_file' => $resourceImage,
            // 'resource_input_file' => $resourceFile,
            'action' => 'draft',
            'tags' => json_encode(['tag1', 'tag2']),
            'metadata' => json_encode([
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ],
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ]
            ]),
            'categories' => json_encode([$category1->uid, $category2->uid]),
            'contact_emails' => json_encode(['email1@example.com', 'email2@example.com']),
            'learning_results' => json_encode([$learningResult1->uid, $learningResult2->uid]),
            // Otros campos necesarios para la creación
        ];

        // Realiza la solicitud POST para crear un nuevo recurso
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', $data);

        // Verifica que la respuesta sea 200 y el recurso se haya creado correctamente
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Recurso añadido correctamente']);


        // :::::::::::: Completando las validaciones necesarias ::::::::://
        // Prepara los datos para crear un nuevo recurso
        $data = [
            'title'                         => 'Nuevo Recurso Educativo',
            'description'                   => 'Descripción del recurso',
            'educational_resource_type_uid' => $educationalResourceTypes->uid,
            'status_uid' => generateUuid(),
            'license_type_uid'              => $licenseTypes->uid,
            'resource_way'                  => 'FILE',
            'resource_input_file'           => 'text.txt',
            'action'                        => 'draft',
            'tags'                          => json_encode(['tag1', 'tag2']),
            'metadata' => json_encode([
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ],
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ]
            ]),
            'categories' => json_encode([$category1->uid, $category2->uid]),
            'contact_emails' => json_encode(['email1@example.com', 'email2@example.com']),
            'learning_results' => json_encode([$learningResult1->uid, $learningResult2->uid]),
            // Otros campos necesarios para la creación
        ];
        // Realiza la solicitud POST para crear un nuevo recurso
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', $data);

        // Verifica que la respuesta sea 200 y el recurso se haya creado correctamente
        $response->assertStatus(200);

        // Prepara los datos para crear un nuevo recurso
        $data = [
            'title'                         => 'Nuevo Recurso Educativo',
            'description'                   => 'Descripción del recurso',
            'educational_resource_type_uid' => $educationalResourceTypes->uid,
            'status_uid' => generateUuid(),
            'license_type_uid'              => $licenseTypes->uid,
            'resource_way'                  => 'URL',
            'resource_url'                  => 'http://miweb.com',
            'action'                        => 'draft',
            'tags'                          => json_encode(['tag1', 'tag2']),
            'metadata' => json_encode([
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ],
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ]
            ]),
            'categories' => json_encode([$category1->uid, $category2->uid]),
            'contact_emails' => json_encode(['email1@example.com', 'email2@example.com']),
            'learning_results' => json_encode([$learningResult1->uid, $learningResult2->uid]),
            // Otros campos necesarios para la creación
        ];
        // Realiza la solicitud POST para crear un nuevo recurso
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', $data);

        // Verifica que la respuesta sea 200 y el recurso se haya creado correctamente
        $response->assertStatus(200);
    }

    /** @test Puedo actualizar un recurso educativo existente correctamente */
    public function testCanUpdateAnExistingEducationalResource()
    {
        // Simula el almacenamiento de archivos
        Storage::fake('public');

        $user = UserSmodel::factory()->create();

        $this->actingAs($user);

        $licenseTypes = LicenseTypesModel::factory()->create()->first();

        // Crea un recurso educativo existente en la base de datos
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create([
                'license_type_uid' => $licenseTypes->uid,
                'title' => 'Recurso existente',
                'description' => 'Descripción existente',
            ])->first();

        EducationalResourcesTagsModel::factory()->count(2)->create(
            [
                'educational_resource_uid' => $resource->uid
            ]
        );

        EducationalResourcesEmailContactsModel::factory()->count(2)->create(
            [
                'educational_resource_uid' => $resource->uid
            ]
        );

        EducationalResourcesEmbeddingsModel::factory()->create(
            [
                'educational_resource_uid'=> $resource->uid
            ]
        );


        // Configura el mock de general_options con la clave correcta
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'necessary_approval_resources' => true, // Corrige el nombre de la clave aquí
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));
        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);


        // Crea algunas categorías y resultados de aprendizaje para asociar con el recurso
        $category1 = CategoriesModel::factory()->create()->first();
        $category2 = CategoriesModel::factory()->create()->first();
        $learningResult1 = LearningResultsModel::factory()->withCompetence()->create();
        $learningResult2 = LearningResultsModel::factory()->withCompetence()->create();

        $educationalResourceTypes = EducationalResourceTypesModel::factory()->create()->first();


        // Prepara los datos para actualizar el recurso
        $data = [
            'educational_resource_uid' => $resource->uid,
            'title' => 'Recurso existente',
            'description' => 'Descripción existente',
            'educational_resource_type_uid' => $educationalResourceTypes->uid,
            'license_type_uid' => $licenseTypes->uid,
            'resource_way' => 'URL',
            'resource_url' => 'https://example.com/resource',
            'action' => 'update',
            'tags' => json_encode([]),
            'metadata' => json_encode([
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ],
                [
                    'uid' => null, // Esto simula un nuevo metadato que no tiene uid, o puedes omitir este campo
                    'metadata_key' => 'Nombre del Meta',
                    'metadata_value' => 'Valor del Meta'
                ]
            ]),
            'categories' => json_encode([$category1->uid, $category2->uid]),
            'contact_emails' => json_encode([]),
            'learning_results' => json_encode([$learningResult1->uid, $learningResult2->uid]),
            // Otros campos necesarios para la creación
        ];

        // Realiza la solicitud POST para actualizar el recurso existente
        $response = $this->postJson('/learning_objects/educational_resources/save_resource', $data);

        // Verifica que la respuesta sea 200 y el recurso se haya actualizado correctamente
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Recurso añadido correctamente']);

        // Verifica que el recurso haya sido actualizado en la base de datos
        $this->assertDatabaseHas('educational_resources', [
            'uid' => $resource->uid,
            'title' => 'Recurso existente',
            'license_type_uid' => $licenseTypes->uid,
            'resource_url' => 'https://example.com/resource',
        ]);

        // Verifica que los metadatos se hayan guardado correctamente
        $this->assertDatabaseHas('educational_resources_metadata', ['metadata_key' => 'Nombre del Meta', 'metadata_value' => 'Valor del Meta']);

        // Verifica que las categorías se hayan actualizado correctamente
        $this->assertDatabaseHas('educational_resource_categories', ['category_uid' => $category1->uid]);
        $this->assertDatabaseHas('educational_resource_categories', ['category_uid' => $category2->uid]);

        // Verifica que los resultados de aprendizaje se hayan actualizado correctamente
        $this->assertDatabaseHas('educational_resources_learning_results', ['learning_result_uid' => $learningResult1->uid]);
        $this->assertDatabaseHas('educational_resources_learning_results', ['learning_result_uid' => $learningResult2->uid]);
    }



    /** @test error de exeption al ser mas de 100 consultas */
    public function testSaveEducationalResourceWithMoreThan100()
    {
        $user = UserSmodel::factory()->create();

        $this->actingAs($user);

        $uids = [];

        $learning_results = LearningResultsModel::factory()->withCompetence()->count(101)->create();

        foreach ($learning_results as $learning_result) {
            $uids[] = [
                $learning_result->uid
            ];
        }

        $response = $this->postJson('/learning_objects/educational_resources/save_resource', [
            'learning_results' => json_encode($uids),
        ]);

        $response->assertStatus(406);
        $response->assertJson(['message' => 'No se pueden seleccionar más de 100 resultados de aprendizaje']);
    }


    /**
     * @test Regenera los embeddings para un recurso educativo.
     */
    public function testEducationalResourcesRegenerateEmbeddings()
    {
        // Crear un usuario con rol de 'MANAGEMENT' y autenticarlo
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generateUuid()]
        ]);
        Auth::login($user);

        // Crear un recurso educativo de prueba
        $educationalResource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->create(
                [
                    'creator_user_uid' => $user->uid,
                ]
            )->first();

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);

        // Configurar el mock para `getEmbedding` y devolver un embedding simulado
        $mockEmbeddingsService->shouldReceive('getEmbedding')
            ->andReturn(array_fill(0, 1536, 0.1));

        // Configurar el mock para `generateEmbeddingForEducationalResource` y verificar que se llama correctamente
        $mockEmbeddingsService->shouldReceive('generateEmbeddingForEducationalResource')
            ->with(Mockery::on(function ($resource) use ($educationalResource) {
                return $resource->uid === $educationalResource->uid;
            }))
            ->andReturnNull();

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        // Enviar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_resources/regenerate_embeddings', [
            'educational_resources_uids' => [$educationalResource->uid],
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se han regenerado los embeddings correctamente']);
    }

    /** @test Puedo eliminar recursos educativos exitosamente */
    public function testCanDeleteEducationalResourcesSuccessfully()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Crea algunos recursos educativos en la base de datos
        $resource1 = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();
        $resource2 = EducationalResourcesModel::factory()->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Prepara los UIDs de los recursos a eliminar
        $resourcesUids = [$resource1->uid, $resource2->uid];

        // Realiza la solicitud DELETE a la ruta con los UIDs
        $response = $this->deleteJson('/learning_objects/educational_resources/delete_resources', [
            'resourcesUids' => $resourcesUids,
        ]);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Recursos eliminados correctamente']);

        // Verifica que los recursos hayan sido eliminados de la base de datos
        $this->assertDatabaseMissing('educational_resources', ['uid' => $resource1->uid]);
        $this->assertDatabaseMissing('educational_resources', ['uid' => $resource2->uid]);
    }

    /**
     * @test  Verifica que se actualizan correctamente los estados de los recursos educativos.
     */
    public function testChangeStatusesResourcesUpdatesResourceStatusesCorrectly()
    {
        // Crear un usuario con rol de 'MANAGEMENT' y autenticarlo
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'STUDENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generateUuid()]
        ]);
        Auth::login($user);

        $category1 = CategoriesModel::factory()->create()->first();

        $user->categories()->attach($category1->uid, [
            'uid' => generateUuid(),
        ]);

        $automaticNotificationType = AutomaticNotificationTypesModel::factory()->create([
            'code' => 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS',
        ]);


        $user->automaticGeneralNotificationsTypesDisabled()->attach($automaticNotificationType->uid, [
            'uid' => generateUuid(),
        ]);


        $status = EducationalResourceStatusesModel::where('code', 'PUBLISHED')->first();

        // Crear recursos educativos de prueba
        $resource1 = EducationalResourcesModel::factory()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create(['status_uid' => $status->uid]);
        $resource2 = EducationalResourcesModel::factory()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create(['status_uid' => $status->uid]);

        $resource1->categories()->attach($category1, ['uid' => generateUuid() ]);

        // Datos de la solicitud
        $changesResourcesStatuses = [
            ['uid' => $resource1->uid, 'status' => $status->code, 'status_uid' => $status->uid],
            ['uid' => $resource2->uid, 'status' => $status->code, 'status_uid' => $status->uid],
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_resources/change_statuses_resources', [
            'changesResourcesStatuses' => $changesResourcesStatuses
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se han actualizado los estados de los recursos correctamente']);

        // Verificar que los estados de los recursos en la base de datos han sido actualizados
        foreach ($changesResourcesStatuses as $change) {
            $this->assertDatabaseHas('educational_resources', [
                'uid' => $change['uid'],
                'status_uid' => $change['status_uid'],
            ]);
        }
    }

    //Pendiente
    /**
     * @test  Verifica que se actualizan correctamente los estados de los recursos educativos.
     */
    public function testChangeStatusesResourcesUpdatesResourceStatusesCorrectlyCodePublished()
    {
        // Crear un usuario con rol de 'MANAGEMENT' y autenticarlo
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generateUuid()]
        ]);
        Auth::login($user);

        $status = EducationalResourceStatusesModel::factory()->create([
            'code' => 'PUBLISHED',
        ])->latest()->first();

        // Crear recursos educativos de prueba
        $resource1 = EducationalResourcesModel::factory()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create(['status_uid' => $status->uid]);
        $resource2 = EducationalResourcesModel::factory()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create(['status_uid' => $status->uid]);

        // Datos de la solicitud
        $changesResourcesStatuses = [
            ['uid' => $resource1->uid, 'status' => $status->code, 'status_uid' => $status->uid],
            ['uid' => $resource2->uid, 'status' => $status->code, 'status_uid' => $status->uid],
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_resources/change_statuses_resources', [
            'changesResourcesStatuses' => $changesResourcesStatuses
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se han actualizado los estados de los recursos correctamente']);

        // Verificar que los estados de los recursos en la base de datos han sido actualizados
        foreach ($changesResourcesStatuses as $change) {
            $this->assertDatabaseHas('educational_resources', [
                'uid' => $change['uid'],
            ]);
        }
    }

    /**
     * @test  Verifica que se devuelve un error cuando no se envían los datos correctamente.
     */
    public function testChangeStatusesResourcesReturnsErrorWhenNoDataSent()
    {
        // Realizar la solicitud POST sin enviar datos
        $response = $this->postJson('/learning_objects/educational_resources/change_statuses_resources', []);

        // Verificar que la respuesta sea 406 (Not Acceptable)
        $response->assertStatus(406);
        $response->assertJson(['message' => 'No se han enviado los datos correctamente']);
    }
}
