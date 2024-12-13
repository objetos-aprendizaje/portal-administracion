<?php

namespace Tests\Unit;


use Mockery;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use App\Models\BlocksModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\ElementsModel;
use App\Models\SubblocksModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use PhpParser\Node\Stmt\Block;
use App\Models\CategoriesModel;
use App\Models\LmsSystemsModel;
use App\Models\CompetencesModel;
use App\Models\CoursesTagsModel;
use App\Models\CourseTypesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use App\Models\CoursesAccesesModel;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use App\Services\EmbeddingsService;
use Illuminate\Support\Facades\App;
use App\Models\CoursesStudentsModel;
use App\Models\CoursesTeachersModel;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\CertidigitalService;
use Illuminate\Support\Facades\Queue;
use App\Models\CoursesEmbeddingsModel;
use Illuminate\Support\Facades\Schema;
use App\Models\CertificationTypesModel;
use Illuminate\Support\Facades\Request;
use App\Models\CoursesPaymentTermsModel;
use App\Models\EducationalProgramsModel;
use App\Models\CompetenceFrameworksModel;
use App\Models\CertidigitalAssesmentsModel;
use App\Exceptions\OperationFailedException;
use App\Models\CertidigitalCredentialsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\CompetenceFrameworksLevelsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Jobs\SendChangeStatusCourseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\CourseLearningResultCalificationsModel;
use App\Models\CoursesBlocksLearningResultsCalificationsModel;
use App\Http\Controllers\Management\ManagementCoursesController;


class ManagementCoursesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    public function testIndexReturnsViewCoursesCorrectly()
    {

        // Crear mocks de los servicios
        $embeddingsServiceMock = $this->createMock(EmbeddingsService::class);
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Instanciar el controlador con los mocks
        $controller = new ManagementCoursesController($embeddingsServiceMock, $certidigitalServiceMock);

        // Simulando datos para los modelos
        $courses = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->count(3)->create();
        $calls = CallsModel::factory()->count(2)->create();
        $coursesStatuses = CourseStatusesModel::factory()->count(2)->create();
        $educationalProgramsTypes = EducationalProgramTypesModel::factory()->count(2)->create();
        $coursesTypes = CourseTypesModel::factory()->count(2)->create();
        $centers = CentersModel::factory()->count(2)->create();
        // Simulando categorías anidadas
        $parentCategory = CategoriesModel::factory()->create();
        $childCategory = CategoriesModel::factory()->create(['parent_category_uid' => $parentCategory->uid]);

        $lmsSystems = LmsSystemsModel::factory()->count(2)->create();
        $certificationTypes = CertificationTypesModel::factory()->count(2)->create(
            [
                'category_uid' => $parentCategory->uid,
            ]
        );

        $teachers = UsersModel::factory()
            // ->hasAttached(UserRolesModel::factory()->create(['code' => 'TEACHER']))
            ->count(2)->create();

        foreach ($teachers as $teacher) {
            $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]); // Crea roles de prueba
            $teacher->roles()->attach($roles->uid, ['uid' => generate_uuid()]);
        }

        $students = UsersModel::factory()
            // ->hasAttached(UserRolesModel::factory()->create(['code' => 'STUDENT']))
            ->count(3)->create();

        foreach ($students as $student) {
            $roles = UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generate_uuid()]); // Crea roles de prueba
            $student->roles()->attach($roles->uid, ['uid' => generate_uuid()]);
        }

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();
        // $user->roles()->attach(UserRolesModel::factory()->create(['code' => 'ADMIN']));
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        // Simulando la respuesta del método y del controlador para la llave API de OpenAI
        // config(['general_options.openai_key' => 'fake_openai_key']);
        // Datos simulados de `general_options`
        $generalOptionsMock = [
            'openai_key' => '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',
            'operation_by_calls' => true, // Agrega el valor específico que se utiliza en la vista
            'enabled_recommendation_module' => 1
        ];

        // Inyecta `general_options` en la aplicación y lo comparte en las vistas
        app()->instance('general_options', $generalOptionsMock);


        View::share('general_options', $generalOptionsMock);

        View::share('general_options', $generalOptionsMock);

        View::share('roles', $roles);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);

        // Enviando la solicitud GET
        $response = $this->get(route('courses'));

        // Asegurando que se carga correctamente con un código 200
        $response->assertStatus(200);

        // Comprobando que los datos se pasen a la vista
        $response->assertViewIs('learning_objects.courses.index')
            ->assertViewHasAll([
                'courses',
                'calls',
                'courses_statuses',
                'educationals_programs_types',
                'courses_types',
                'teachers',
                'students',
                'categories',
                'lmsSystems',
                'centers',
                'certificationTypes',
                'educational_programs',
                'variables_js'
            ]);
    }

    /**
     * @test
     * Prueba la creación y actualización de un curso.
     */
    public function testSaveCourseCreationAndUpdate()
    {
        // Crear un usuario con permisos de gestión      

        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        // Asignar el mock a app('general_options')  
        GeneralOptionsModel::factory()->create(
            [
                'option_name' => 'openai_key',
                'option_value' => 'miclave_open_api'
            ]
        );

        $generalUpdate = GeneralOptionsModel::where('option_name', 'certidigital_url')->first();
        $generalUpdate->option_value = "web";
        $generalUpdate->save();
        // $managementUser->assignRole('MANAGEMENT');
        // $this->actingAs($managementUser);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        app()->instance('general_options', $generalOptionsMock);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'title' => 'Curso 1',
                'description' => 'Descripción del curso de prueba',
                'validate_student_registrations' => 0,
                'educational_program_type_uid' => $educational_program_type->uid,
                'payment_mode' => 'SINGLE_PAYMENT',
            ]
        )->first();

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        // Simular un archivo
        $image = UploadedFile::fake()->image('featured_big_carrousel_image.jpg');

        // Crear datos de prueba para la creación de un curso
        $newCourseData = [
            'title' => 'Curso 1',
            'description' => 'Descripción del curso de prueba',
            'course_type_uid' => $course->course_type_uid,
            'structure' => json_encode(['module1', 'module2']),
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'action' => 'draft',
            'ects_workload' => 1,
            'validate_student_registrations' => 1,
            'cost' => 150,
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'evaluation_criteria' => 'Nuevos Criterios de Evaluación',
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',
            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'documents' => json_encode([]),
            'payment_mode' => $course->payment_mode,
            'belongs_to_educational_program' => false,
            'featured_big_carrousel_image_path' => $image,
            // 'image_input_file'=>$image
        ];


        // Enviar solicitud POST para crear un nuevo curso
        $response = $this->postJson('/learning_objects/courses/save_course', $newCourseData);


        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha añadido el curso correctamente']);

        // Verificar que el curso fue guardado en la base de datos
        $this->assertDatabaseHas('courses', [
            'title' => 'Curso 1',
            'description' => 'Descripción del curso de prueba',
        ]);

        // Crear un curso existente para la prueba de actualización
        $existingCourse = CoursesModel::factory()
            ->withCourseStatus()->withCourseType()->create([
                'title' => 'Curso Existente',
                'description' => 'Descripción del curso existente',
            ]);
        
            CoursesEmbeddingsModel::factory()->create([
                'course_uid' => $existingCourse->uid,
            ]);

        $teachersNoCoordinators = UsersModel::factory()->create();
        $teachersCoordinators = UsersModel::factory()->create();


        // Datos de prueba para la actualización de un curso
        $updateCourseData = [
            'course_uid' => $existingCourse->uid,
            'title' => 'Curso Existente',
            'description' => 'Descripción del curso existente',
            'structure' => json_encode(['module1', 'module3']),
            'action' => 'update',
            'belongs_to_educational_program' => false,
            'course_type_uid' => $existingCourse->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'documents' => json_encode([]),
            'teacher_no_coordinators' => json_encode([$teachersNoCoordinators->uid]),
            'teacher_coordinators' => json_encode([$teachersCoordinators->uid]),

        ];

        // Enviar solicitud POST para actualizar el curso existente
        $response = $this->postJson('/learning_objects/courses/save_course', $updateCourseData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);

        // Verificar que el curso fue actualizado en la base de datos
        $this->assertDatabaseHas('courses', [
            'uid' => $existingCourse->uid,
            'title' => 'Curso Existente',
            'description' => 'Descripción del curso existente',
        ]);
    }
    /**
     * @test
     * Prueba de actualización de un curso. con estado ADDED_EDUCATIONAL_PROGRAM
     */
    public function testSaveCourseUpdateState()
    {
        // Crear un usuario con permisos de gestión      

        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        // Asignar el mock a app('general_options')  
        GeneralOptionsModel::factory()->create(
            [
                'option_name' => 'openai_key',
                'option_value' => 'miclave_open_api'
            ]
        );

        app()->instance('general_options', $generalOptionsMock);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $status = CourseStatusesModel::where('code', 'ADDED_EDUCATIONAL_PROGRAM')->first();

        $educational_program = EducationalProgramsModel::factory()->create(
            [
                'educational_program_type_uid' => $educational_program_type->uid,
                'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
                'inscription_finish_date' => Carbon::now()->addDays(29)->format('Y-m-d\TH:i'),
                'enrolling_start_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),
                'enrolling_finish_date' => Carbon::now()->addDays(60)->format('Y-m-d\TH:i'),
                'realization_start_date' => Carbon::now()->addDays(61)->format('Y-m-d\TH:i'),
                'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
            ]
        )->first();

        // Crear un curso existente para la prueba de actualización
        $existingCourse = CoursesModel::factory()
            ->withCourseStatus()->withCourseType()->create([
                'title' => 'Curso Existente',
                'course_status_uid' => $status->uid,
                'educational_program_type_uid' => $educational_program_type->uid,
                'educational_program_uid' => $educational_program->uid,
                'description' => 'Descripción del curso existente',
            ])->first();


        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $existingCourse->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $categorie = CategoriesModel::factory()->create();

        $learning = LearningResultsModel::factory()->withCompetence()->count(2)->create()->first();

        // Datos de prueba para la actualización de un curso       
        $updateCourseData = [
            'course_uid' => $existingCourse->uid,
            'title' => 'Curso Actualizado',
            'description' => 'Descripción actualizada del curso',
            'action' => 'update',
            'belongs_to_educational_program' => false,
            'course_type_uid' => $existingCourse->course_type_uid,
            'educational_program_type_uid' => $existingCourse->educational_program_type_uid,

            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(15)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->addDays(20)->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),

            // Nueva fecha de inicio y fin de realización dentro del rango
            'realization_start_date' => Carbon::now()->addDays(62)->format('Y-m-d\TH:i'), // Igual al inicio del programa
            'realization_finish_date' => Carbon::now()->addDays(89)->format('Y-m-d\TH:i'), // Igual al fin del programa
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                $categorie->uid
            ]),
            'structure' => json_encode(
                [
                    [
                        'uid'             => generate_uuid(),
                        'type'            => 'EVALUATION',
                        'name'            => 'name1',
                        'description'     => 'description1',
                        'order'           => 1,
                        'learningResults' => [$learning->uid],
                        'subBlocks' => [
                            [
                                'uid' => null,
                                'name'        => 'subname1',
                                'description' => 'subdescription1',
                                'order'       => 1,
                                'elements'    => [
                                    [
                                        'uid' => null,
                                        'name'            => 'name1',
                                        'description'     => 'description1',
                                        'order'           => 1,
                                        'subElements' => [
                                            [
                                                'uid' => null,
                                                'name'            => 'name1',
                                                'description'     => 'description1',
                                                'order'           => 1,
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'documents' => json_encode([]),
        ];


        // Enviar solicitud POST para crear un nuevo curso
        $response = $this->postJson('/learning_objects/courses/save_course', $updateCourseData);


        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);

        // Verificar que el curso fue actualizado en la base de datos
        $this->assertDatabaseHas('courses', [
            'uid' => $existingCourse->uid,
            'title' => 'Curso Actualizado',
            'description' => 'Descripción actualizada del curso',
        ]);

        // Con error de fecha en realization_start_date y realization_finish_date
        $updateCourseData2 = [
            'course_uid' => $existingCourse->uid,
            'title' => 'Curso Actualizado',
            'description' => 'Descripción actualizada del curso',
            'structure' => json_encode(['module1', 'module3']),
            'action' => 'update',
            'belongs_to_educational_program' => false,
            'course_type_uid' => $existingCourse->course_type_uid,
            'educational_program_type_uid' => $existingCourse->educational_program_type_uid,

            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(15)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->addDays(20)->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),

            // Nueva fecha de inicio y fin de realización dentro del rango
            'realization_start_date' => Carbon::now()->addDays(50)->format('Y-m-d\TH:i'), // Igual al inicio del programa
            'realization_finish_date' => Carbon::now()->addDays(91)->format('Y-m-d\TH:i'), // Igual al fin del programa
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'documents' => json_encode([]),
        ];

        // Enviar solicitud POST para crear un nuevo curso
        $response = $this->postJson('/learning_objects/courses/save_course', $updateCourseData2);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(422);
    }

    /**
     * @test
     * Prueba de actualización de un curso. con estado ADDED_EDUCATIONAL_PROGRAM
     */
    public function testSaveCourseEducationalProgram422()
    {
        // Crear un usuario con permisos de gestión      

        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        // Asignar el mock a app('general_options')  
        GeneralOptionsModel::factory()->create(
            [
                'option_name' => 'openai_key',
                'option_value' => 'miclave_open_api'
            ]
        );

        app()->instance('general_options', $generalOptionsMock);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $status = CourseStatusesModel::where('code', 'ADDED_EDUCATIONAL_PROGRAM')->first();

        $educational_program = EducationalProgramsModel::factory()->create(
            [
                'educational_program_type_uid' => $educational_program_type->uid,

            ]
        )->first();

        // Crear un curso existente para la prueba de actualización
        $existingCourse = CoursesModel::factory()
            ->withCourseType()->create([
                'title' => 'Curso Existente',
                'course_status_uid' => $status->uid,
                'educational_program_type_uid' => $educational_program_type->uid,
                'educational_program_uid' => $educational_program->uid,
                'description' => 'Descripción del curso existente',
            ])->first();


        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $existingCourse->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        // Datos de prueba para la actualización de un curso       
        $updateCourseData = [
            'course_uid' => $existingCourse->uid,
            'title' => 'Curso Actualizado',
            'description' => 'Descripción actualizada del curso',
            'structure' => json_encode(['module1', 'module3']),
            'action' => 'update',
            'belongs_to_educational_program' => false,
            'course_type_uid' => $existingCourse->course_type_uid,
            'educational_program_type_uid' => $existingCourse->educational_program_type_uid,

            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(15)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->addDays(20)->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),

            // Nueva fecha de inicio y fin de realización dentro del rango
            'realization_start_date' => Carbon::now()->addDays(50)->format('Y-m-d\TH:i'), // Igual al inicio del programa
            'realization_finish_date' => Carbon::now()->addDays(95)->format('Y-m-d\TH:i'), // Igual al fin del programa
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'documents' => json_encode([]),
        ];

        // Enviar solicitud POST para crear un nuevo curso
        $response = $this->postJson('/learning_objects/courses/save_course', $updateCourseData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(422);
    }


    /**
     * @test
     * Prueba de actualización de un curso con error 422 en validacion ValidateCourseFields 
     */
    public function testSaveCourseValidateCourseFields422()
    {
        // Crear un usuario con permisos de gestión      

        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        // Asignar el mock a app('general_options')  
        GeneralOptionsModel::factory()->create(
            [
                'option_name' => 'openai_key',
                'option_value' => 'miclave_open_api'
            ]
        );

        app()->instance('general_options', $generalOptionsMock);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        // $status = CourseStatusesModel::where('code', 'ADDED_EDUCATIONAL_PROGRAM')->first();

        // $educational_program = EducationalProgramsModel::factory()->create(
        //     [
        //         'educational_program_type_uid' => $educational_program_type->uid,

        //     ]
        // )->first();

        // // Crear un curso existente para la prueba de actualización
        // $existingCourse = CoursesModel::factory()
        //     ->withCourseType()->withCourseStatus()->create([
        //         'title' => 'Curso Existente',
        //         'course_status_uid' => $status->uid,
        //         'educational_program_type_uid' => $educational_program_type->uid,
        //         'educational_program_uid' => $educational_program->uid,
        //         'description' => 'Descripción del curso existente',
        //     ])->first();


        // CoursesEmbeddingsModel::factory()->create([
        //     'course_uid' => $existingCourse->uid,
        // ]);

        $type = CourseTypesModel::factory()->create()->first();

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        // Datos de prueba para la actualización de un curso       
        $updateCourseData = [
            // 'course_uid' => $existingCourse->uid,
            'title' => 'Nuevo curso',
            'description' => 'Descripción del curso',
            'action' => 'submit',
            'belongs_to_educational_program' => false,
            'course_type_uid' => $type->uid,
            // 'educational_program_type_uid' => $existingCourse->educational_program_type_uid,
            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(15)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->addDays(20)->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),

            // Nueva fecha de inicio y fin de realización dentro del rango
            'realization_start_date' => Carbon::now()->addDays(50)->format('Y-m-d\TH:i'), // Igual al inicio del programa
            'realization_finish_date' => Carbon::now()->addDays(95)->format('Y-m-d\TH:i'), // Igual al fin del programa
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',

            'featured_big_carrousel' => "Titulo",
            'featured_big_carrousel_title' => 'Título del carrusel', // Campo requerido si se usa el carrusel
            'featured_big_carrousel_description' => 'Descripción del carrusel', // Campo requerido si se usa el carrusel
            'featured_slider_color_font' => '#FFFFFF', // Color del texto del slider

            // Validación de imagen destacada
            'featured_big_carrousel_image_path' => null, // Cambiar a una ruta válida si es necesario
            // Validación de criterios de evaluación
            'evaluation_criteria' => json_encode(['Criterio 1', 'Criterio 2']), // 

            // Nuevos campos para validar profesores
            'teacher_coordinators' => json_encode(['profesor1@example.com', 'profesor2@example.com']), // Lista de profesores que son coordinadores
            'teacher_no_coordinators' => json_encode(['profesor1@example.com', 'profesor2@example.com']), // Lista de profesores que no son coordinadores

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'documents' => json_encode([]),
        ];

        // Enviar solicitud POST para crear un nuevo curso
        $response = $this->postJson('/learning_objects/courses/save_course', $updateCourseData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(422);
    }


    public function testSaveCourseCreatesNewCourseWithRoleManagement()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        // $course_type =  CourseTypesModel::factory()->create()->first();
        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array',
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'validate_student_registrations' => 0,
                'educational_program_type_uid' => $educational_program_type->uid,
                'description' => 'Porro et distinctio ab inventore sit',
                'featured_big_carrousel_description' => 'Harum facilis consequatur aut nesciunt ut esse aliquid'
                // 'course_status_uid' => $status->uid,
            ]
        )->first();

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,

        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        // Convertir los tags a un JSON string
        $tags = json_encode([]); // '["tag1","tag2"]'

        // Asegúrate de que el JSON string sea válido
        // if (json_last_error() !== JSON_ERROR_NONE) {
        //     throw new Exception('Error en la conversión JSON');
        // }

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        $ct = CoursesTagsModel::factory()->count(3)->create([
            'course_uid' => $course->uid,
        ]);

        CategoriesModel::factory()->count(3)->create();

        $learning = LearningResultsModel::factory()->withCompetence()->count(2)->create()->first();

        $datos_course = [
            'course_uid' => null,
            'action' => 'draft',
            'belongs_to_educational_program' => false,
            'title' => $course->title,
            'description' => 'Porro et distinctio ab inventore sit',
            'course_type_uid' => $course->course_type_uid,
            // 'course_status_uid' => $course->course_status_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'realization_start_date' => "2024-06-10",
            'realization_finish_date' => "2024-08-20",
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',
            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'structure' => json_encode([
                [
                    'uid'             => generate_uuid(),
                    'type'            => 'EVALUATION',
                    'name'            => 'name1',
                    'description'     => 'description1',
                    'order'           => 1,
                    'learningResults' => [$learning->uid],
                    'subBlocks' => [
                        [
                            'uid'         => generate_uuid(),
                            'name'        => 'subname1',
                            'description' => 'subdescription1',
                            'order'       => 1,
                            'elements'    => [
                                [
                                    'uid' => generate_uuid(),
                                    'name'            => 'name1',
                                    'description'     => 'description1',
                                    'order'           => 1,
                                    'subElements' => [
                                        [
                                            'uid' => generate_uuid(),
                                            'name'            => 'name1',
                                            'description'     => 'description1',
                                            'order'           => 1,
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],

            ]),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'tags' => json_encode([
                'tag1',
                'tag2'
            ]),
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),

            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),

            // 'tags' => $tags,
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $datos_course);

        $response->assertStatus(200);

        $response->assertJson(['message' => 'Se ha añadido el curso correctamente']);

        $this->assertDatabaseHas('courses',  [
            'creator_user_uid' => $user->uid,
            // 'course_status_uid' => $status->uid,
            // otros datos que quieras verificar
        ]);
    }


    public function testSaveCourseCreatesNewCourseValidatePaymentTerms422()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        // $course_type =  CourseTypesModel::factory()->create()->first();
        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array',
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'validate_student_registrations' => 0,
                'educational_program_type_uid' => $educational_program_type->uid,
                'description' => 'Porro et distinctio ab inventore sit',
                'featured_big_carrousel_description' => 'Harum facilis consequatur aut nesciunt ut esse aliquid'
                // 'course_status_uid' => $status->uid,
            ]
        )->first();

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,

        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);
        $ct = CoursesTagsModel::factory()->count(3)->create([
            'course_uid' => $course->uid,
        ]);

        $learning = LearningResultsModel::factory()->withCompetence()->count(2)->create()->first();


        // Validar error cuando  $previousFinishDate && $startDate < $previousFinishDate
        $datos_course = [
            'course_uid' => null,
            'action' => 'draft',
            'belongs_to_educational_program' => false,
            'title' => $course->title,
            'description' => 'Porro et distinctio ab inventore sit',
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'realization_start_date' => "2024-06-10",
            'realization_finish_date' => "2024-08-20",
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',
            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'structure' => json_encode([]),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'tags' => json_encode([
                'tag1',
                'tag2'
            ]),
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ],
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(2)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $datos_course);

        $response->assertStatus(422);

        // Validar error cuando  $startDate > $finishDate
        $datos_course = [
            'course_uid' => null,
            'action' => 'draft',
            'belongs_to_educational_program' => false,
            'title' => $course->title,
            'description' => 'Porro et distinctio ab inventore sit',
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'realization_start_date' => "2024-06-10",
            'realization_finish_date' => "2024-08-20",
            'ects_workload' => 1,
            'validate_student_registrations' => 0,
            'center_uid' => $center->uid,
            'calification_type' => 'TEXTUAL',
            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'structure' => json_encode([]),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'tags' => json_encode([
                'tag1',
                'tag2'
            ]),
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(15)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ],
            ]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $datos_course);

        $response->assertStatus(422);
    }

    public function testSaveCourseUpdatesExistingCourse()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $uidCourse = generate_uuid();
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'validate_student_registrations' => 0,
            'uid' => $uidCourse,
            'educational_program_type_uid' => $educational_program_type->uid,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
        ]);

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $generalOptionsMock = [
            'operation_by_calls' => false,
            'necessary_approval_editions' => true,
            'some_option_array' => [],
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        App::instance('general_options', $generalOptionsMock);


        $requestData = [
            'course_uid' => $uidCourse,
            'action' => 'update',
            'title' => $course->title,
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => true,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            // 'realization_start_date' => Carbon::now(),
            // 'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL'

        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);
        $this->assertDatabaseHas('courses', [
            'uid' => $uidCourse,
            // otros datos que quieras verificar
        ]);

        
        //:::::::::::::  Esta sección es para cubir linea 690  :::::::::::::::::::::::: ///

        $cert = CertidigitalCredentialsModel::factory()->create()->first();

        $educational = EducationalProgramsModel::factory()->create(
            [
                'educational_program_type_uid' => $educational_program_type->uid,
                'certidigital_credential_uid'=> $cert->uid
            ]
        )->first();

        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'validate_student_registrations' => 0,
            'uid' => generate_uuid(),
            'educational_program_type_uid' => $educational_program_type->uid,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'educational_program_uid' => $educational->uid
        ]);

        $requestData = [
            'course_uid' => $course1->uid,
            'action' => 'update',
            'title' => $course1->title,
            'validate_student_registrations' => $course1->validate_student_registrations,
            'course_type_uid' => $course1->course_type_uid,
            'educational_program_type_uid' => $course1->educational_program_type_uid,
            'belongs_to_educational_program' => true,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            // 'realization_start_date' => Carbon::now(),
            // 'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL'
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);
        $this->assertDatabaseHas('courses', [
            'uid' => $course1->uid,
            // otros datos que quieras verificar
        ]);

    }

    public function testSaveCourseWithBelongsEducationalProgram()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $course_status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        $uidCourse = generate_uuid();


        $course = CoursesModel::factory()->withCourseType()->create([
            'uid' => $uidCourse,
            'validate_student_registrations' => 0,
            'course_status_uid' => $course_status->uid,
            'educational_program_type_uid' => $educational_program_type->uid,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
        ]);

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $generalOptionsMock = [
            'operation_by_calls' => false,
            'necessary_approval_editions' => true,
            'some_option_array' => [],
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        App::instance('general_options', $generalOptionsMock);


        $requestData = [
            'course_uid' => $uidCourse,
            'action' => 'submit',
            'title' => $course->title,
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => true,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            // 'realization_start_date' => Carbon::now(),
            // 'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL'

        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);
        $this->assertDatabaseHas('courses', [
            'uid' => $uidCourse,
            // otros datos que quieras verificar
        ]);

        $requestData = [
            'course_uid' => $uidCourse,
            'action' => 'draft',
            'title' => $course->title,
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => true,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            // 'realization_start_date' => Carbon::now(),
            // 'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL'
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
    }



    public function testSaveCourseUpdatesExistingCourseNotManagement()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $course_origin = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $course_status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();


        $uidCourse = generate_uuid();
        $course = CoursesModel::factory()->withCourseType()->create([
            'validate_student_registrations' => 0,
            'uid'                            => $uidCourse,
            'educational_program_type_uid'   => $educational_program_type->uid,
            'payment_mode'                   => 'INSTALLMENT_PAYMENT',
            'course_origin_uid'              => $course_origin->uid,
            'course_status_uid'              => $course_status->uid,
        ]);

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));

        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $generalOptionsMock = [
            'operation_by_calls' => false,
            'necessary_approval_editions' => true,
            'some_option_array' => [],
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        App::instance('general_options', $generalOptionsMock);

        // Simular un archivo
        $image = UploadedFile::fake()->image('featured_big_carrousel_image.jpg');

        $requestData = [
            'course_uid' => $uidCourse,
            'action' => 'submit',
            'course_status_uid'              => $course_status->uid,
            'title' => $course->title,
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => false,
            'payment_mode' => 'INSTALLMENT_PAYMENT',

            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'cost' => 100,
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now(),
            'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),
            'featured_big_carrousel_image_path' => $image,
            'description' => 'Acrtualizar descripción',

        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);
        $this->assertDatabaseHas('courses', [
            'uid' => $uidCourse,
            // otros datos que quieras verificar
        ]);


        $course_status = CourseStatusesModel::where('code', 'UNDER_CORRECTION_APPROVAL')->first();

        $course = CoursesModel::factory()->withCourseType()->create([
            'validate_student_registrations' => 0,
            'educational_program_type_uid'   => $educational_program_type->uid,
            'course_status_uid'              => $course_status->uid,
        ]);

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        $requestData = [
            'course_uid' => $course->uid,
            'action' => 'submit',
            'course_status_uid'              => $course_status->uid,
            'title' => $course->title,
            'description' => 'Actualizar descripción',
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => false,
            'payment_mode' => 'INSTALLMENT_PAYMENT',

            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'cost' => 100,
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now(),
            'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),

        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);

        $course_status = CourseStatusesModel::where('code', 'UNDER_CORRECTION_PUBLICATION')->first();

        $course = CoursesModel::factory()->withCourseType()->create([
            'validate_student_registrations' => 0,
            'educational_program_type_uid'   => $educational_program_type->uid,
            'course_status_uid'              => $course_status->uid,
        ]);

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        $requestData = [
            'course_uid' => $course->uid,
            'action' => 'submit',
            'course_status_uid'              => $course_status->uid,
            'title' => $course->title,
            'description' => 'Actualizar descripción',
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => false,
            'payment_mode' => 'INSTALLMENT_PAYMENT',

            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'cost' => 100,
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now(),
            'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'structure' => json_encode([]),
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),

            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),

        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
    }

    public function testSaveCourseUpdatesExistingCourseManagement()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $course_origin = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $course_status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        $lms = LmsSystemsModel::factory()->create();


        $uidCourse = generate_uuid();
        $course = CoursesModel::factory()->withCourseType()->create([
            'validate_student_registrations' => 0,
            'uid'                            => $uidCourse,
            'educational_program_type_uid'   => $educational_program_type->uid,
            'payment_mode'                   => 'INSTALLMENT_PAYMENT',
            'course_origin_uid'              => $course_origin->uid,
            'course_status_uid'              => $course_status->uid,
            'lms_url' => null,
            'lms_system_uid' => $lms->uid,

        ]);

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));
        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $generalOptionsMock = [
            'operation_by_calls'            => false,
            'necessary_approval_editions'   => true,
            'some_option_array'             => [],
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        App::instance('general_options', $generalOptionsMock);


        $requestData = [
            'course_uid' => $uidCourse,
            'action' => 'submit',
            'course_status_uid'              => $course_status->uid,
            'title' => $course->title,
            'validate_student_registrations' => $course->validate_student_registrations,
            'course_type_uid' => $course->course_type_uid,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => false,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'lms_system_uid' => $lms->uid,

            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'cost' => 100,
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now(),
            'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode([]),

        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se ha actualizado el curso correctamente']);
        $this->assertDatabaseHas('courses', [
            'uid' => $uidCourse,
            // otros datos que quieras verificar
        ]);
    }

    public function testSaveCourseWithValidateStructureGreaterThan100()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        $center = CentersModel::factory()->create()->first();

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'educational_program_type_uid' => $educational_program_type->uid,
                'validate_student_registrations' => 0,
            ]
        );
        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);


        $generalOptionsMock = [
            'operation_by_calls'            => false,
            'necessary_approval_editions'   => true,
            'some_option_array'             => [],
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];
        App::instance('general_options', $generalOptionsMock);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);
        $mockEmbeddingsService->shouldReceive('getEmbedding')->andReturn(array_fill(0, 1536, 0.1));
        // Reemplazar el servicio real por el mock en el contenedor de servicios de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);

        $learningResult = [];


        $learnings = LearningResultsModel::factory()->count(101)->withCompetence()->create();

        foreach ($learnings as $learning) {
            $learningResult[] = [
                $learning->uid
            ];
        }

        $lms = LmsSystemsModel::factory()->create();

        $requestData = [
            'action' => 'submit',
            'course_type_uid' => $course->course_type_uid,
            'title' => 'Nuevo Curso',
            'validate_student_registrations' => $course->validate_student_registrations,
            'educational_program_type_uid' => $course->educational_program_type_uid,
            'belongs_to_educational_program' => false,
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'lms_system_uid' => $lms->uid,
            'inscription_start_date' => "2024-05-10",
            'inscription_finish_date' => "2024-05-10",
            'cost' => 100,
            'realization_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addMonth()->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now(),
            'realization_finish_date' => Carbon::now()->addMonth(),
            'ects_workload' => 1,
            'center_uid' => $center->uid,
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'calification_type' => 'TEXTUAL',
            'payment_terms' => json_encode([
                [
                    'uid'         => $coursePayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => $coursePayment->cost,
                ]
            ]),
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([
                generate_uuid()
            ]),
            'structure' => json_encode(
                [
                    [
                        'uid'             => generate_uuid(),
                        'type'            => 'EVALUATION',
                        'name'            => 'name1',
                        'description'     => 'description1',
                        'order'           => 1,
                        'learningResults' => $learningResult,
                    ]
                ]
            ),
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(422);

        $response->assertJson(['message' => 'No puedes añadir más de 100 resultados de aprendizaje por bloque']);
    }

    /** @test Validacion al tratar de Guardar curso */
    public function testSaveCourseFailsWithValidationErrors()
    {
        $user = UsersModel::factory()->create();
        Auth::login($user);

        $generalOptionsMock = [
            'operation_by_calls' => true, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        $requestData = [
            'course_uid' => null,
            'action' => 'submit',
            'belongs_to_educational_program' => false,
            // datos que causen un error de validación
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /** @test Validacion al tratar de Guardar curso */
    public function testSaveCourseFailsWithValidationErrorsCheckStatus()
    {
        $user = UsersModel::factory()->create();
        Auth::login($user);

        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();

        $educational_program =  EducationalProgramsModel::factory()->create(
            [
                'educational_program_type_uid' => $educational_program_type->uid,
            ]
        );

        $status = CourseStatusesModel::where('code', 'ENROLLING')->first();

        $course = CoursesModel::factory()->withCourseType()->create(
            [
                'course_status_uid' => $status->uid,
                'educational_program_uid' => $educational_program->uid,
                'validate_student_registrations' => 0,
                'belongs_to_educational_program' => false
            ]
        );

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        $generalOptionsMock = [
            'operation_by_calls' => true, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        $requestData = [
            'course_uid' => $course->uid,
            'action' => 'submit',
            'belongs_to_educational_program' => false,
            // datos que causen un error de validación
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No puedes editar un curso que no esté en estado de introducción o subsanación',
        ]);



        $statusEducational = EducationalProgramStatusesModel::where('code', 'ENROLLING')->first();

        $educational_program =  EducationalProgramsModel::factory()->create(
            [
                'educational_program_type_uid' => $educational_program_type->uid,
                'educational_program_status_uid' => $statusEducational->uid,
            ]
        );

        $status = CourseStatusesModel::where('code', 'ADDED_EDUCATIONAL_PROGRAM')->first();

        $course = CoursesModel::factory()->withCourseType()->create(
            [
                'course_status_uid' => $status->uid,
                'educational_program_uid' => $educational_program->uid,
                'validate_student_registrations' => 0,
                'belongs_to_educational_program' => true,
            ]
        );

        $requestData = [
            'course_uid' => $course->uid,
            'action' => 'submit',
            'belongs_to_educational_program' => true,
            // datos que causen un error de validación
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $requestData);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No puedes editar un curso cuyo programa formativo no esté en estado de introducción o subsanación',
        ]);
    }

    /**
     * @test
     * Este test verifica que el método changeStatusesCourses actualiza correctamente los estados de los cursos
     * cuando se proporcionan los datos correctos y se realizan todas las operaciones necesarias sin errores.
     */
    public function testChangeStatusesCoursesSuccessfully()
    {
        // Simular los datos de entrada
        $uid = generate_uuid();

        $userCreator = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]); // Crea roles de prueba
        $userCreator->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($userCreator);

        $lms = LmsSystemsModel::factory()->create();

        // Crear un curso con un estado inicial
        $course = CoursesModel::factory()->withCourseType()->create([
            'creator_user_uid' => $userCreator->uid,
            'lms_url' => null,
            'lms_system_uid' => $lms->uid,
            'course_status_uid' => CourseStatusesModel::where('code', 'PENDING_APPROVAL')->first()->uid,
            'status_reason' => 'por prueba',
        ]);

        // Simular los datos de entrada para el cambio de estado
        $changesCoursesStatuses = [
            [
                'uid' => $course->uid,
                // 'status' => 'ACCEPTED',
                'status' => 'ACCEPTED_PUBLICATION',
                'reason' => 'Test reason'
            ]
        ];

        // Fake para evitar el envío real de correos y manejo de la cola
        Queue::fake();

        // Crear la solicitud con los datos de entrada simulados
        $request = Request::create('/learning_objects/courses/change_statuses_courses', 'POST', [
            'changesCoursesStatuses' => $changesCoursesStatuses
        ]);

        // Crear mocks del certificado
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Create a mock for EmbeddingsService     
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked services
        $controller = new ManagementCoursesController($mockEmbeddingsService, $certidigitalServiceMock);
        $response = $controller->changeStatusesCourses($request);

        // Verificar que la respuesta sea la esperada
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Se han actualizado los estados de los cursos correctamente', $response->getData()->message);

        // Verificar que el estado del curso se haya actualizado
        $course->refresh(); // Recargar el curso desde la base de datos
        $this->assertEquals('ACCEPTED_PUBLICATION', $course->status->code);

        // Verificar que el trabajo de envío de notificación fue despachado con los datos correctos usando reflexión
        Queue::assertPushed(SendChangeStatusCourseNotification::class, function ($job) use ($course) {
            // Usar Reflection para acceder a la propiedad protegida 'course'
            $reflection = new \ReflectionClass($job);
            $courseProperty = $reflection->getProperty('course');
            $courseProperty->setAccessible(true);
            $courseInJob = $courseProperty->getValue($job);

            return $courseInJob->uid === $course->uid;
        });
    }

    /** @test Arroja una excepción si no se envían datos. Cambia los estados del curso */
    public function testThrowsExceptionIfNoDataIsSentChangesCourseStatuses()
    {
        // Enviar petición sin datos
        $response = $this->post('/learning_objects/courses/change_statuses_courses', [
            'changesCoursesStatuses' => null,
        ]);

        // Verificar la respuesta
        $response->assertStatus(406);
        $response->assertJson(['message' => 'No se han enviado los datos correctamente']);
    }

    /** @test Arroja una excepción si no se los datos del status es invalido */
    public function testChangeStatusesCoursesWithInvalidStatus()
    {
        // Crea un curso válido
        $course = CoursesModel::factory()->withCourseType()
            ->withCourseStatus()->create();

        // Simula la petición con un estado inválido
        $response = $this->postJson('/learning_objects/courses/change_statuses_courses', [
            'changesCoursesStatuses' => [
                [
                    'uid' => $course->uid,
                    'status' => 'INVALID_STATUS',
                    'reason' => 'Estado no válido'
                ]
            ]
        ]);

        // Verifica que se devuelva el error esperado
        $response->assertStatus(406);
        $response->assertJson(['message' => "El estado es incorrecto"]);
    }


    /**
     * @test
     * Este test verifica que se lanza una excepción si uno de los cursos no existe.
     */
    public function testThrowsExceptionIfCourseNotExist()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Asignar un rol específico al usuario (por ejemplo, el rol 'ADMINISTRATOR')
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        // Autenticar al usuario
        Auth::login($user);

        // Crear un curso válido
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'uid' => generate_uuid(),
            'creator_user_uid' => $user->uid,
        ]);

        // Simular un cambio de estado para un curso que no existe
        $changesCoursesStatuses = [
            ['uid' => generate_uuid(), 'status' => 'ACCEPTED_PUBLICATION', 'reason' => 'Approved for publication'],
        ];

        // Crear mocks del certificado
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Create a mock for EmbeddingsService
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService, $certidigitalServiceMock);

        // Crear una instancia de la solicitud con los datos simulados
        $request = Request::create('/learning_objects/courses/change_statuses_courses', 'POST', [
            'changesCoursesStatuses' => $changesCoursesStatuses,
        ]);

        // Esperar que se lance la excepción OperationFailedException
        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('Uno de los cursos no existe');

        // Ejecutar el método del controlador
        $controller->changeStatusesCourses($request);
    }



    /** @Group Duplicatecourse */
    /** @test Duplicar curso*/
    public function testDuplicateACourse()
    {

        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Autenticar al usuario
        $this->actingAs($user);

        // Crear un curso de prueba
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'belongs_to_educational_program' => false,
        ]);

        // Crear un estado "INTRODUCTION" para los cursos
        $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        // Hacer la solicitud POST a la ruta de duplicación
        $response = $this->postJson("/learning_objects/courses/duplicate_course/{$course->uid}", [
            'course_uid' => $course->uid,
        ]);

        // Verificar que la respuesta sea 200
        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Curso duplicado correctamente',
        ]);

        // Verificar que el curso haya sido duplicado en la base de datos
        $this->assertDatabaseHas('courses', [
            'title' => $course->title . " (copia)",
            'course_status_uid' => $status->uid,
        ]);


        // Verificar que el nuevo curso tenga un UID diferente
        $newCourse = CoursesModel::where('title', $course->title . " (copia)")->first();
        $this->assertNotEquals($course->uid, $newCourse->uid);
    }

    public function testDuplicateACourseFail()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Autenticar al usuario
        $this->actingAs($user);

        // Crear un curso de prueba que pertenezca a un programa formativo
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'belongs_to_educational_program' => true,
        ]);

        // Crear un estado "INTRODUCTION" para los cursos
        $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        // Hacer la solicitud POST a la ruta de duplicación
        $response = $this->postJson("/learning_objects/courses/duplicate_course/{$course->uid}", [
            'course_uid' => $course->uid,
        ]);

        // Verificar que la respuesta tenga un código de estado 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Verificar que la respuesta contenga el mensaje de error esperado
        $response->assertJson([
            'message' => 'No puedes duplicar un curso que pertenezca a un programa formativo',
        ]);

        // Verificar que no se haya duplicado el curso en la base de datos
        $this->assertDatabaseMissing('courses', [
            'title' => $course->title . " (copia)",
        ]);
    }


    public function testStatusCourseEdition()
    {
        // Crear un mock del modelo CourseStatusesModel
        $mockStatus = Mockery::mock(CourseStatusesModel::class);
        $mockStatus->shouldReceive('whereIn')
            ->with('code', ['INTRODUCTION', 'ACCEPTED_PUBLICATION', 'PENDING_APPROVAL'])
            ->andReturn(collect([
                (object)['code' => 'INTRODUCTION'],
                (object)['code' => 'ACCEPTED_PUBLICATION'],
                (object)['code' => 'PENDING_APPROVAL'],
            ]));

        // Reemplazar el modelo en el contenedor de Laravel
        $this->app->instance(CourseStatusesModel::class, $mockStatus);

        // Crear un mock de la configuración general
        app()->instance('general_options', ['necessary_approval_editions' => true]);

        // Crear mocks de los certificado
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Create a mock for EmbeddingsService
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService, $certidigitalServiceMock);

        // Use Reflection to access the private method applyFilters
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('statusCourseEdition');
        $method->setAccessible(true);

        $method2 = $reflectionClass->getMethod('statusCourseEdition');
        //Todo: probar si funciona de esta forma o no. 
        $method2->setAccessible(false);



        // Caso 1: Acción "draft" sin estado actual
        $course_bd = (object)['status' => null];
        $result = $method->invokeArgs($controller, ['draft', $course_bd]);
        $this->assertEquals('INTRODUCTION', $result->code);

        // Caso 2: Acción "submit" con aprobación necesaria
        $course_bd = (object)['status' => (object)['code' => 'INTRODUCTION']];
        $result = $method->invokeArgs($controller, ['submit', $course_bd]);
        $this->assertEquals('PENDING_APPROVAL', $result->code);

        // Caso 3: Acción "submit" sin aprobación necesaria
        app()->instance('general_options', ['necessary_approval_editions' => false]);
        $result = $method->invokeArgs($controller, ['submit', $course_bd]);
        $this->assertEquals('ACCEPTED_PUBLICATION', $result->code);

        // Caso 4: Acción "update" sin aprobación necesaria

        $course_bd = (object)['status' => (object)['code' => 'INTRODUCTION']];
        $result = $method->invokeArgs($controller, ['update', $course_bd]);
        // $this->assertEquals('null', $result->code);

    }


    // Cierra Mockery después de las pruebas
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testApplyFiltersCourses()
    {

        // Crear tipos de programas educativos
        $educational_programType1 = EducationalProgramTypesModel::factory()->create()->latest()->first();

        $center1 = CentersModel::factory()->create([
            'uid'  => generate_uuid(),
            'name' => 'Centro 1'
        ])->latest()->first();

        $coursestatuses1 = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $course_type1 = CourseTypesModel::factory()->create()->first();

        $teacher1 = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
        $teacher1->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Crear datos de prueba
        $course1 = CoursesModel::create([
            'uid' => generate_uuid(),
            'center_uid' => $center1->uid,
            'title' => 'Curso 1',
            'description' => 'Description',
            'course_status_uid' => $coursestatuses1->uid,
            'course_type_uid' => $course_type1->uid,
            'educational_program_type_uid' => $educational_programType1->uid,
            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(29)->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now()->addDays(61)->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
            'ects_workload' => 10,
            'identifier' => 'CUR-8154744',
            'cost' => 100,
            'min_required_students' => 5,
            'creator_user_uid' => $teacher1->uid,
            'payment_mode' => 'SINGLE_PAYMENT'
        ])->first();

        $course1->update(['center_uid' => $center1->uid]);

        $course1->teachers()->attach($teacher1, ['uid' => generate_uuid()]);

        $coordinator = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
        $coordinator->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Crear Coordinador de curso
        $existingCourseCoordinator = CoursesTeachersModel::where('course_uid', $course1->uid)
            ->where('user_uid', $coordinator->uid)
            ->first();

        if (!$existingCourseCoordinator) {
            // Create a new course coordinator entry if it doesn't exist
            $coursecoordinator = CoursesTeachersModel::factory()->create([
                'uid' => generate_uuid(),
                'course_uid' => $course1->uid,
                'user_uid' => $coordinator->uid,
                'type' => 'COORDINATOR'
            ])->first();
            $course1->teachers()->attach($coursecoordinator->user_uid, ['uid' => generate_uuid()]);
        }

        // Crear No-Coordinador de curso
        $existingCourseNoCoordinator = CoursesTeachersModel::where('course_uid', $course1->uid)
            ->where('type', 'NO_COORDINATOR')
            ->first();

        if (!$existingCourseNoCoordinator) {
            // Create a new course coordinator entry if it doesn't exist
            $coursenocoordinator = CoursesTeachersModel::factory()->create([
                'uid' => generate_uuid(),
                'course_uid' => $course1->uid,
                'user_uid' => $coordinator->uid,
                'type' => 'COORDINATOR'
            ])->first();
            $course1->teachers()->attach($coursenocoordinator->user_uid, ['uid' => generate_uuid()]);
        }


        $category1 = CategoriesModel::factory()->create()->first();
        $course1->categories()->attach($category1->uid, ['uid' => generate_uuid()]);


        $course1->update(['course_status_uid' => $coursestatuses1->uid]);


        $call1 = CallsModel::factory()->create()->latest()->first();
        $course1->update(['call_uid' => $call1->uid]);


        $educational_program1 = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->latest()->first();
        $course1->update(['educational_program_uid ' => $educational_program1->uid]);


        $course1->update(['course_type_uid' => $course_type1->uid]);

        // Crear un bloque y asociarlo con el curso
        $block = BlocksModel::factory()->create(['uid' => generate_uuid(), 'course_uid' => $course1->uid]);

        // Crear una competencia
        $competence = CompetencesModel::factory()->create()->latest()->first();

        // Asociar la competencia con el bloque
        $block->competences()->attach($competence->uid, ['uid' => generate_uuid()]);

        // Crear mocks de los servicios
        $embeddingsServiceMock = $this->createMock(EmbeddingsService::class);
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Instanciar el controlador con los mocks
        $controller = new ManagementCoursesController($embeddingsServiceMock, $certidigitalServiceMock);

        // Use Reflection to access the private method applyFilters
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('applyFilters');
        $method->setAccessible(true);

        // Prepare any parameters needed for applyFilters
        $parameters = ['exampleData']; // Adjust this based on what applyFilters expects


        $inscrip_date1 = Carbon::now()->format('Y-m-d\TH:i');
        $inscrip_date2 = Carbon::now()->addDays(30)->format('Y-m-d\TH:i');

        //Caso 2 Filtrar por fecha Inscription Rango
        $filtersDate = [['database_field' => 'inscription_date', 'value' => [$inscrip_date1, $inscrip_date2]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersDate, &$query]);
        $filteredCoursesByDate = $query->get();
        // Verificar que el curso devuelto está dentro del rango de fechas
        $this->assertEquals($course1->uid, $filteredCoursesByDate->first()->uid);

        // Caso 3: Filtrar por fecha de inscripción única
        $filters = [['database_field' => 'inscription_date', 'value' => [$inscrip_date1]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filters, &$query]);
        $this->assertGreaterThan(0, CoursesModel::count());

        // Caso 4: Filtrar por fecha de realización Rango
        $date_realization1 = Carbon::now()->addDays(61)->format('Y-m-d\TH:i');
        $date_realization2 = Carbon::now()->addDays(90)->format('Y-m-d\TH:i');
        $filtersRealization = [['database_field' => 'realization_date', 'value' => [$date_realization1, $date_realization2]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersRealization, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 5: Filtrar por fecha de realización (fecha única)
        $filters = [['database_field' => 'realization_date', 'value' => [$date_realization1]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filters, &$query]);
        $this->assertGreaterThan(0, CoursesModel::count());

        // Caso 6: Filtrar por Tipo de teachers Coordinador
        $filtersCreator = [['database_field' => 'coordinators_teachers', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersCreator, &$query]);
        $this->assertEmpty($query->get());

        // Caso 7: Filtrar por Tipo de teachers No-Coordinador
        $filtersCreator = [['database_field' => 'no_coordinators_teachers', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersCreator, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 8: Filtrar por usuario creador
        $filtersCreator = [['database_field' => 'creator_user_uid', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersCreator, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 9: Filtrar por categorías
        $filterscategory = [['database_field' => 'categories', 'value' => [$category1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filterscategory, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 10: Filtrar por estados del curso
        $filtersstatus = [['database_field' => 'course_statuses', 'value' => [$course1->course_status_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersstatus, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 11: Filtrar por convocatorias
        $filterscall = [['database_field' => 'calls', 'value' => [$call1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filterscall, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 12: Filtrar por programas educativos
        $filtersep = [['database_field' => 'educational_programs', 'value' => [$educational_program1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersep, &$query]);
        $this->assertEmpty($query->get());



        // Caso 13: Filtrar por tipos de curso
        $filterstype = [['database_field' => 'course_types', 'value' => [$course_type1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filterstype, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 14: Filtrar por carga de ECTS mínima
        $filtersminwork = [['database_field' => 'min_ects_workload', 'value' => 10]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersminwork, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 15: Filtrar por carga de ECTS mínima
        $filtersmaxwork = [['database_field' => 'max_ects_workload', 'value' => 10]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmaxwork, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 16: Filtrar por coste mínimo
        $filtersmincost = [['database_field' => 'min_cost', 'value' => 100]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmincost, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 17: Filtrar por coste máximo
        $filtersmaxcost = [['database_field' => 'max_cost', 'value' => 100]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmaxcost, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 18: Filtrar por estudiantes requeridos mínimos
        $filtersminstudent = [['database_field' => 'min_required_students', 'value' => 5]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersminstudent, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 19: Filtrar por estudiantes requeridos máximos
        $filtersmaxstudent = [['database_field' => 'max_required_students', 'value' => 7]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmaxstudent, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 20: Filtrar por competencias
        $filterscompetence = [['database_field' => 'learning_results', 'value' => [$competence->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filterscompetence, &$query]);

        // Caso 21: Cualquier otro campo
        $filtersep = [['database_field' => 'evaluation_criteria', 'value' => 'EV']];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersep, &$query]);
        $this->assertEmpty($query->get());

        // Caso 22: Filtrar por embeddings = 1
        $embeddings = [['database_field' => 'embeddings', 'value' => 1]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$embeddings, &$query]);
        // $this->assertNotEmpty($query->get());

        // Caso 22: Filtrar por embeddings = 0
        $embeddings_0 = [['database_field' => 'embeddings', 'value' => 0]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$embeddings_0, &$query]);
        // $this->assertNotEmpty($query->get());

        $center = [['database_field' => 'center', 'value' => 'prueba']];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$center, &$query]);

        $validate_student = [['database_field' => 'validate_student_registrations', 'value' => 1]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$validate_student, &$query]);
    }

    /**
     * @test
     * Prueba que se obtienen las calificaciones de un curso correctamente.
     */
    public function testGetCourseCalifications()
    {
        // Crear un estudiante simulado para el curso
        $student = UsersModel::factory()->create(
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]
        )->first();

        $this->actingAs($student);

        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseType()
            ->withCourseStatus()
            ->create([
                'belongs_to_educational_program' => false
            ])
            ->first();

        $course->students()->attach($student->uid, ['uid' => generate_uuid()]);

        // dd($course->students);

        $learningResult = LearningResultsModel::factory()->withCompetence()->create()->first();

        $block = BlocksModel::factory()->create(
            [
                'course_uid' => $course->uid,
            ]
        )->first();

        // Crear bloques y resultados de aprendizaje simulados
        CoursesBlocksLearningResultsCalificationsModel::factory()->create(
            [
                'user_uid' => $student->uid,
                'course_block_uid' => $block->uid,
                'learning_result_uid' => $learningResult->uid
            ]
        );

        // Preparar los datos de búsqueda y ordenamiento
        $data = [
            'size' => 10,
            'search' => 'John Doe', // Simular la búsqueda de un nombre
            'sort' => [
                ['field' => 'first_name', 'dir' => 'asc']
            ]
        ];

        // Realizar la solicitud POST a la ruta con el curso_uid
        $response = $this->postJson('/learning_objects/courses/get_califications/' . $course->uid, $data);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contiene las claves necesarias
        $response->assertJsonStructure([
            'coursesStudents',
            'courseBlocks',
            'learningResults'
        ]);

        // Verificar que las calificaciones y bloques de aprendizaje se obtienen correctamente
        $responseData = $response->json();
        // $this->assertGreaterThan(0, count($responseData['coursesStudents']['data']));
        $this->assertGreaterThan(0, count($responseData['courseBlocks']));
        // $this->assertGreaterThan(0, count($responseData['learningResults']));
    }

    /**
     * @test
     * Prueba que se obtienen las calificaciones de un curso correctamente.
     */
    public function testGetCourseCalificationsWitEeducationalProgram()
    {
        // Crear un estudiante simulado para el curso
        $student = UsersModel::factory()->create(
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]
        )->first();

        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create();


        $this->actingAs($student);

        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseType()
            ->withCourseStatus()
            ->create([
                'belongs_to_educational_program' => true,
                'educational_program_uid' => $educationalProgram->uid
            ])
            ->first();

        $educationalProgram->students()->attach($student->uid, [
            'uid' => generate_uuid(),
            'acceptance_status' => 'PENDING'
        ]);

        $compentenceFramework = CompetenceFrameworksModel::factory()->create()->first();

        $compentence = CompetencesModel::factory()->create(
            [
                'competence_framework_uid' => $compentenceFramework->uid
            ]
        )->first();

        $learningResult = LearningResultsModel::factory()->create([
            'competence_uid' => $compentence->uid,
        ])->first();

        $block = BlocksModel::factory()->create(
            [
                'course_uid' => $course->uid,
            ]
        )->first();

        $block->learningResults()->attach($learningResult->uid, [
            'uid' => generate_uuid(),
        ]);

        // Crear bloques y resultados de aprendizaje simulados
        
        CoursesBlocksLearningResultsCalificationsModel::factory()->create(
            [
                'user_uid' => $student->uid,
                'course_block_uid' => $block->uid,
                'learning_result_uid' => $learningResult->uid
            ]
        );

        CourseLearningResultCalificationsModel::factory()->create([
            "user_uid" => $student->uid,
            "course_uid" => $course->uid,
            "learning_result_uid" => $learningResult->uid,
        ]);

        // Preparar los datos de búsqueda y ordenamiento
        $data = [
            'size' => 10,
            'search' => 'John Doe', // Simular la búsqueda de un nombre
            'sort' => [
                ['field' => 'first_name', 'dir' => 'asc']
            ]
        ];

        // Realizar la solicitud POST a la ruta con el curso_uid
        $response = $this->postJson('/learning_objects/courses/get_califications/' . $course->uid, $data);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contiene las claves necesarias
        $response->assertJsonStructure([
            'coursesStudents',
            'courseBlocks',
            'learningResults'
        ]);

        // Verificar que las calificaciones y bloques de aprendizaje se obtienen correctamente
        $responseData = $response->json();
        // $this->assertGreaterThan(0, count($responseData['coursesStudents']['data']));
        $this->assertGreaterThan(0, count($responseData['courseBlocks']));
        // $this->assertGreaterThan(0, count($responseData['learningResults']));
    }




    /**
     * @test
     * Prueba que se calcula correctamente la mediana de inscripciones por categoría.
     */
    public function testCalculateMedianEnrollingsCategories()
    {
        // Crear categorías simuladas
        $categories = CategoriesModel::factory()->count(3)->create();

        $student = UsersModel::factory()->create();
        $this->actingAs($student);

        $status = CourseStatusesModel::where("code", "FINISHED")->first();


        foreach ($categories as $category) {
            $course = CoursesModel::factory()
                ->withCourseType()
                ->create(
                    [
                        'course_status_uid' => $status->uid,
                    ]
                );

            $course->students()->attach(
                $student->uid,
                [
                    'uid' => generate_uuid(),
                    'status' => 'ENROLLED',
                    'acceptance_status' => 'ACCEPTED',
                ]
            );

            $course->categories()->attach($category->uid, [
                'uid' => generate_uuid()
            ]);
        }

        // Preparar los datos de la solicitud
        $requestData = [
            'categories_uids' => $categories->pluck('uid')->toArray(),
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->postJson('/learning_objects/courses/calculate_median_enrollings_categories', $requestData);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que el resultado contenga el campo 'median'
        $responseData = $response->json();
        $this->assertArrayHasKey('median', $responseData);

        // Verificar que la mediana sea mayor que 0
        $this->assertGreaterThan(0, $responseData['median']);
    }

    /** 
     * @test 
     * Simula la regeneración de embeddings para un curso específico 
     */
    public function testRegenerateEmbeddings()
    {
        // Configurar un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create()
            ->first();

        CoursesEmbeddingsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        // Crear un mock del servicio de embeddings
        $mockEmbeddingsService = Mockery::mock(EmbeddingsService::class);

        // Simular la llamada a getEmbedding para que devuelva un embedding predefinido
        $mockEmbeddingsService->shouldReceive('getEmbedding')
            ->andReturn(array_fill(0, 1536, 0.1));

        // Simular la llamada a generateEmbeddingForCourse para que use el mock de getEmbedding
        //  $mockEmbeddingsService->shouldReceive('generateEmbeddingForCourse')
        //      ->with($course)
        //      ->andReturnTrue();
        // Simular la llamada a generateEmbeddingForCourse con cualquier instancia de CoursesModel
        $mockEmbeddingsService->shouldReceive('generateEmbeddingForCourse')
            ->with(Mockery::type(CoursesModel::class))
            ->andReturnTrue();

        // Reemplazar el servicio real por el mock en el contenedor de Laravel
        $this->app->instance(EmbeddingsService::class, $mockEmbeddingsService);


        // Ejecutar la solicitud POST a la ruta
        $response = $this->postJson('/learning_objects/courses/regenerate_embeddings', [
            'courses_uids' => [$course->uid],
        ]);

        // Verificar que la respuesta tenga el código de éxito 200 y el mensaje esperado
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se han regenerado los embeddings correctamente']);
    }

    /** @test  Obtener cursos como Admainistrator*/
    public function testAllCoursesForAdministrator()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        // Crear cursos de ejemplo
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        // Simular la solicitud
        $response = $this->postJson('/learning_objects/courses/get_courses');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        $this->assertCount(CoursesModel::count(), $response->json('data'));
    }

    /** @test */
    public function testCoursesForTeacher()
    {
        $teacher = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
        $teacher->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($teacher);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        // Crear cursos cerado por profesor
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['creator_user_uid' => $teacher->uid]);
        // Crear cursos crado por un usuario diferente a profesor
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        // Suponiendo que el profesor está asignado a algunos cursos
        $course3 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(); // Curso creado por otro usuario
        $course3->teachers_coordinate()->attach($teacher->uid, ['uid' => generate_uuid()]); // Asignar al profesor como coordinador

        // Simular la solicitud
        $response = $this->postJson('/learning_objects/courses/get_courses');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
    }

    /** @test */
    public function testCoursesBasedOnSearch()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Crear cursos de ejemplo
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Mathematics']);
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Science']);
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'History']);


        // Simular la búsqueda de cursos
        $response = $this->postJson('/learning_objects/courses/get_courses?search=Math');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        // Asegúrate de que solo se devuelva el curso que coincide con la búsqueda
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Mathematics', $response->json('data')[0]['title']);
    }

    /** @test */
    public function testSortsCoursesBasedOn()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Crear cursos de ejemplo
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Science']);
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Mathematics']);
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'History']);

        // Simular la solicitud con parámetros de ordenamiento
        $response = $this->postJson('/learning_objects/courses/get_courses?sort[0][field]=title&sort[0][dir]=asc&size=5');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que los cursos estén ordenados alfabéticamente
        $sortedData = $response->json('data');
        $this->assertEquals('History', $sortedData[0]['title']);
        $this->assertEquals('Mathematics', $sortedData[1]['title']);
        $this->assertEquals('Science', $sortedData[2]['title']);
    }

    /**
     * @test Obtener curso sin CourseUid.
     */
    public function testGetCourseWithoutCourseUid()
    {
        $response = $this->get('/learning_objects/courses/get_course/');

        $response->assertStatus(404); // Since a missing UID will not match the route
    }

    /**
     * @test Para el método getCourse cuando el curso no existe
     */
    public function testGetCourseNotFound()
    {
        $uuid = generate_uuid();
        $response = $this->get('/learning_objects/courses/get_course/' . $uuid);

        $response->assertStatus(406)
            ->assertJson([
                'message' => 'El curso no existe',
            ]);
    }

    /** @test Para el método getCourse cuando el curso existe.
     */
    public function testGetCourseSuccess()
    {
        // Creating a dummy course
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        // Fetching the course
        $response = $this->get('/learning_objects/courses/get_course/' . $course->uid);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'uid' => $course->uid,
            ]);
    }

    /** @test Para el método getCourse cuando el curso existe.
     */
    public function testGetCourseSuccessWithOrder()
    {
        // Creating a dummy course
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $block = BlocksModel::factory()->create(
            [
                'course_uid' => $course->uid,
            ]
        );

        $subBlock = SubblocksModel::factory()->create(
            [
                'block_uid' => $block->uid,
                'order'     => 2,
            ]
        );

        $element = ElementsModel::factory()->create(
            [
                'subblock_uid' => $subBlock->uid,
                'order'     => 1,
            ]
        );


        // Fetching the course
        $response = $this->get('/learning_objects/courses/get_course/' . $course->uid);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'uid' => $course->uid,
            ]);
    }

    /** @test Valida la carga ordenada de subniveles de bloques */
    // public function testGetCourseLoadsOrderedSubLevels()
    // {
    //     // Crea un curso con relaciones complejas de bloques, subBlocks, elements y subElements
    //     $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

    //     // Agrega relaciones y estructura de subniveles
    //     $block = $course->blocks()->create(['order' => 1]);
    //     $subBlock = $block->subBlocks()->create(['order' => 1]);
    //     $element = $subBlock->elements()->create(['order' => 1]);
    //     $element->subElements()->create(['order' => 1]);

    //     // Realiza la solicitud con el UID del curso
    //     $response = $this->getJson("/learning_objects/courses/get_course/{$course->uid}");

    //     // Verifica que la respuesta sea exitosa
    //     $response->assertStatus(200);

    //     // Comprueba que la estructura de datos esté presente y en el orden correcto
    //     $response->assertJsonPath('blocks.0.order', 1);
    //     $response->assertJsonPath('blocks.0.subBlocks.0.order', 1);
    //     $response->assertJsonPath('blocks.0.subBlocks.0.elements.0.order', 1);
    //     $response->assertJsonPath('blocks.0.subBlocks.0.elements.0.subElements.0.order', 1);
    // }


    /**
     * @test Guarda las calificaciones correctamente.
     */
    public function testSaveCalification()
    {

        $educational = EducationalProgramsModel::factory()->withEducationalProgramType()->create();
        // Crear datos de prueba
        $course = CoursesModel::factory()
            ->withCourseType()
            ->withCourseStatus()
            ->create(
                [
                    'educational_program_uid' => $educational->uid
                ]
            );
        $user = UsersModel::factory()->create();

        $block = BlocksModel::factory()->create([
            'course_uid' => $course->uid
        ])->first();

        $compentenceFramework = CompetenceFrameworksModel::factory()->create()->first();

        $compentenceFrameworkLevel = CompetenceFrameworksLevelsModel::factory()->create(
            [
                'competence_framework_uid' => $compentenceFramework->uid
            ]
        )->first();

        $learning = LearningResultsModel::factory()
            ->withCompetence()
            ->create()->first();

        $courseStudent = CoursesStudentsModel::factory()->create(
            [
                'user_uid' => $user->uid,
                'course_uid' => $course->uid
            ]
        );

        $blocksLearningResultCalifications = [
            [
                "userUid" => $user->uid,
                "blockUid" => $block->uid,
                "learningResultUid" => $learning->uid,
                "calificationInfo" => 'Calificación info 1',
                "levelUid" => $compentenceFrameworkLevel->uid,
            ]
        ];

        $learningResultsCalifications = [
            [
                "userUid" => $user->uid,
                "learningResultUid" => $learning->uid,
                "calificationInfo" => 'Calificación info 2',
                "levelUid" => $compentenceFrameworkLevel->uid
            ]
        ];

        $globalCalifications = [
            [
                "user_uid" => $user->uid,
                'calification_info' =>  "Calificación info 2"
            ]
        ];

        $data = [
            'blocksLearningResultCalifications' => $blocksLearningResultCalifications,
            'learningResultsCalifications' => $learningResultsCalifications,
            'globalCalifications' => $globalCalifications

        ];

        // Realizar la solicitud POST
        $response = $this->postJson(
            '/learning_objects/courses/save_calification/' . $course->uid,
            $data
        );

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Se han guardado las calificaciones correctamente']);

        // Verificar que los datos se hayan guardado en la base de datos
        foreach ($blocksLearningResultCalifications as $blockCalification) {
            $this->assertDatabaseHas('courses_blocks_learning_results_califications', [
                "user_uid" => $blockCalification["userUid"],
                "course_block_uid" => $blockCalification["blockUid"],
                "learning_result_uid" => $blockCalification["learningResultUid"],
                "calification_info" => $blockCalification["calificationInfo"],
                "competence_framework_level_uid" => $blockCalification["levelUid"]
            ]);
        }

        foreach ($learningResultsCalifications as $learningResultCalification) {
            $this->assertDatabaseHas('course_learning_result_califications', [
                "user_uid" => $learningResultCalification["userUid"],
                "learning_result_uid" => $learningResultCalification["learningResultUid"],
                "calification_info" => $learningResultCalification["calificationInfo"],
                "competence_framework_level_uid" => $learningResultCalification["levelUid"]
            ]);
        }
    }


    // public function testSendCredentials()
    // {
    //     // Mocking the certidigitalService
    //     $mockCertidigitalService = Mockery::mock('App\Services\CertidigitalService');
    //     $this->app->instance('App\Services\CertidigitalService', $mockCertidigitalService);

    //     // Define what should happen when emissionCredentials is called
    //     $mockCertidigitalService->shouldReceive('emissionCredentials')
    //         ->once()
    //         ->with('test-course-uid');

    //     // Prepare a fake request with course_uid
    //     $response = $this->postJson('/learning_objects/courses/send_credentials', [
    //         'course_uid' => 'test-course-uid',
    //     ]);

    //     // Assert response status and structure
    //     $response->assertStatus(200)
    //              ->assertJson(['message' => 'Se han enviado las credenciales correctamente']);
    // }


    // featured_big_carrousel_image_path


    /** @test Para el método sendCredentials cuando se envían credenciales correctamente. */
    // public function testSendCredentialsSuccess()
    // {
    //     $user = UsersModel::factory()->create()->latest()->first();
    //     $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
    //     $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

    //     Auth::login($user);

    //     $cert_creadential = CertidigitalCredentialsModel::factory()->create()->first();

    //     $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
    //         [
    //             'certidigital_credential_uid'=> $cert_creadential->uid,                
    //         ]
    //     ); 

    //     $course->students()->attach($user->uid,[
    //         'uid'=>generate_uuid(),
    //     ]);


    //     $block = BlocksModel::factory()->create(
    //         [
    //             'course_uid'=>$course->uid
    //         ]
    //     ); 

    //     $learningResult= LearningResultsModel::factory()->withCompetence()->create();

    //     $block->learningResults()->attach($learningResult->uid,[
    //         'uid' => generate_uuid()
    //     ]);   


    //     $cert_assements = CertidigitalAssesmentsModel::factory()->create(
    //         [
    //             'learning_result_uid'=> $learningResult->uid,
    //             'course_uid'=> $course->uid,
    //             // 'certidigital_credential_uid' => $cert_creadential->uid,
    //             'course_block_uid' => $block->uid
    //         ]
    //     );

    //     // Arrange: Mock de datos

    //     $mockRequest = [
    //         'course_uid' => [$course->uid],
    //     ];

    //     // Mock del modelo y servicio
    //     $certidigitalServiceMock = $this->createMock(CertidigitalService::class);
    //     $certidigitalServiceMock->expects($this->once())
    //         ->method('emissionCredentials')
    //         ->with($course->uid);

    //     $this->app->instance(CertidigitalService::class, $certidigitalServiceMock);

    //     // Act: Realizar la solicitud
    //     $response = $this->postJson(route('send-credentials'), $mockRequest);

    //     // Assert: Validar respuesta
    //     $response->assertStatus(200)
    //         ->assertJson(['message' => 'Se han enviado las credenciales correctamente']);
    // }


    public function testSendCredentialsManagementCourses()
    {
        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'MANAGEMENT')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        app()->instance('general_options', $generalOptionsMock);

        $cert_creadential = CertidigitalCredentialsModel::factory()->create()->first();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'certidigital_credential_uid'=> $cert_creadential->uid,      
            ]
        )->first();

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
        ]);

        $block = BlocksModel::factory()->create(
            [
                'course_uid' => $course->uid
            ]
        );

        $learningResult = LearningResultsModel::factory()->withCompetence()->create();

        $block->learningResults()->attach($learningResult->uid, [
            'uid' => generate_uuid()
        ]);


        $cert_assements = CertidigitalAssesmentsModel::factory()->create(
            [
                'learning_result_uid' => $learningResult->uid,
                'course_uid' => $course->uid,
                // 'certidigital_credential_uid' => $cert_creadential->uid,
                'course_block_uid' => $block->uid
            ]
        );

        $data = [
            'course_uid' => [$course->uid],
            'students_uids' => $user->uid
        ];

        $response = $this->postJson('/learning_objects/courses/send_credentials',$data)
        ->assertJson(['message' => 'Credenciales enviadas correctamente']);
    }




    public function testEmitAllCredentialsManagementCoursesManagement()
    {

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'MANAGEMENT')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        app()->instance('general_options', $generalOptionsMock);

        $educationalType = EducationalProgramTypesModel::factory()->create()->first();

        $cert = CertidigitalCredentialsModel::factory()->create()->first();


        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'educational_program_type_uid' => $educationalType->uid,
                'certidigital_credential_uid' => $cert->uid
            ]
        )->first();


        CertidigitalAssesmentsModel::factory()->create(
            [
                'course_uid' => $course->uid
            ]
        );

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
        ]);

        $response = $this->postJson(
            '/learning_objects/courses/emit_all_credentials',
            [
                'course_uid' => $course->uid
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Credenciales emitidas correctamente',
        ]);
    }

    public function testEmitAllCredentialsManagementCoursesTeacher()
    {

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'TEACHER')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        app()->instance('general_options', $generalOptionsMock);

        $educationalType = EducationalProgramTypesModel::factory()->create(
            [
                'managers_can_emit_credentials' => 0,
                'teachers_can_emit_credentials' => 1,
            ]
        )->first();

        $cert = CertidigitalCredentialsModel::factory()->create()->first();


        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'educational_program_type_uid' => $educationalType->uid,
                'certidigital_credential_uid' => $cert->uid
            ]
        )->first();

        CertidigitalAssesmentsModel::factory()->create(
            [
                'course_uid' => $course->uid
            ]
        );

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
        ]);

        $response = $this->postJson(
            '/learning_objects/courses/emit_all_credentials',
            [
                'course_uid' => $course->uid
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Credenciales emitidas correctamente',
        ]);
    }

    public function testEmitAllCredentialsManagementCoursesWithFail()
    {

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);


        $educationalType = EducationalProgramTypesModel::factory()->create(
            [
                'managers_can_emit_credentials' => 0,
                'teachers_can_emit_credentials' => 1,
            ]
        )->first();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'educational_program_type_uid' => $educationalType->uid,
            ]
        )->first();

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
        ]);

        $response = $this->postJson(
            '/learning_objects/courses/emit_all_credentials',
            [
                'course_uid' => $course->uid
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No tienes permisos para emitir credenciales en este curso',
        ]);
    }

    public function testEmitCredentialsManagementCourses()
    {

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'TEACHER')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        app()->instance('general_options', $generalOptionsMock);

        $educationalType = EducationalProgramTypesModel::factory()->create(
            [
                'managers_can_emit_credentials' => 0,
                'teachers_can_emit_credentials' => 1,
            ]
        )->first();

        $cert = CertidigitalCredentialsModel::factory()->create()->first();


        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'educational_program_type_uid' => $educationalType->uid,
                'certidigital_credential_uid' => $cert->uid
            ]
        )->first();

        CertidigitalAssesmentsModel::factory()->create(
            [
                'course_uid' => $course->uid
            ]
        );

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
            // 'emissions_block_uuid' => $cert->uid
        ]);

        $data = [
            'course_uid' => $course->uid,
            'students_uids' => [$user->uid],
        ];


        $response = $this->postJson(
            '/learning_objects/courses/emit_credentials',
            $data
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Credenciales emitidas correctamente',
        ]);
    }

    public function testEmitCredentialsManagementCoursesWithFail()
    {

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'TEACHER')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);



        $educationalType = EducationalProgramTypesModel::factory()->create(
            [
                'managers_can_emit_credentials' => 0,
                'teachers_can_emit_credentials' => 1,
            ]
        )->first();

        $cert = CertidigitalCredentialsModel::factory()->create()->first();


        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'educational_program_type_uid' => $educationalType->uid,
                'certidigital_credential_uid' => $cert->uid
            ]
        )->first();

        CertidigitalAssesmentsModel::factory()->create(
            [
                'course_uid' => $course->uid
            ]
        );

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
            'emissions_block_uuid' => $cert->uid
        ]);

        $data = [
            'course_uid' => $course->uid,
            'students_uids' => [$user->uid],
        ];


        $response = $this->postJson(
            '/learning_objects/courses/emit_credentials',
            $data
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'No se pueden emitir credenciales porque alguno de los alumnos ya tiene credenciales emitidas',
        ]);
    }

    public function testSealCredentialsManagementCourses()
    {

        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();

        $roles = UserRolesModel::where('code', 'TEACHER')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
        ];

        app()->instance('general_options', $generalOptionsMock);      

        $cert = CertidigitalCredentialsModel::factory()->create()->first();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'certidigital_credential_uid' => $cert->uid
            ]
        )->first();

        CertidigitalAssesmentsModel::factory()->create(
            [
                'course_uid' => $course->uid
            ]
        );

        $course->students()->attach($user->uid, [
            'uid' => generate_uuid(),
        ]);

        $data = [
            'course_uid' => $course->uid,
            'students_uids' => [$user->uid],
        ];


        $response = $this->postJson(
            '/learning_objects/courses/seal_credentials',
            $data
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Credenciales selladas correctamente',
        ]);
    }
}
