<?php

namespace Tests\Unit;



use Exception;
use Tests\TestCase;
use RdKafka\Producer;
use App\Models\CallsModel;
use App\Models\UsersModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use App\Services\KafkaService;
use Illuminate\Support\Carbon;
use App\Models\CategoriesModel;
use App\Models\LmsSystemsModel;
use App\Models\CompetencesModel;
use App\Models\CoursesTagsModel;
use App\Models\CourseTypesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use App\Services\EmbeddingsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\CoursesStudentsModel;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\CoursesPaymentTermsModel;
use App\Models\EducationalProgramsModel;
use App\Exceptions\OperationFailedException;
use App\Models\CoursesStudentDocumentsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramStatusesModel;
use App\Jobs\SendChangeStatusCourseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Management\ManagementCoursesController;


class LearningObjectCoursesTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }
    /** @test  Test Index View  */
    public function testIndexReturnsViewCourses()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]); // Crea roles de prueba
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


        // Crear datos
        CoursesModel::factory()->withCourseStatus()->withCourseType()->count(3)->create();
        CallsModel::factory()->count(2)->create();
        CourseStatusesModel::factory()->count(2)->create();
        EducationalProgramTypesModel::factory()->count(2)->create();
        CourseTypesModel::factory()->count(2)->create();
        CentersModel::factory()->count(2)->create();
        UsersModel::factory()->count(5)->create();
        CategoriesModel::factory()->count(3)->create();
        EducationalProgramsModel::factory()->withEducationalProgramType()->count(2)->create();
        CompetencesModel::factory()->count(3)->create();
        LmsSystemsModel::factory()->count(2)->create();
        // Asegúrate de que la opción se crea correctamente
        GeneralOptionsModel::create([
            'option_name' => 'operation_by_calls',
            'option_value' => 1,
        ])->first();

        // Realizar la solicitud a la ruta
        $response = $this->get(route('courses'));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertViewIs('learning_objects.courses.index');
        $response->assertViewHas('page_name', 'Cursos');
        $response->assertViewHas('courses');
        $response->assertViewHas('calls');
        $response->assertViewHas('courses_statuses');
        $response->assertViewHas('educationals_programs_types');
        $response->assertViewHas('courses_types');
        $response->assertViewHas('teachers');
        $response->assertViewHas('students');
        $response->assertViewHas('categories');
        $response->assertViewHas('educational_programs');


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

        // Crear un curso con un estado inicial
        $course = CoursesModel::factory()->withCourseType()->create([
            'uid' => generate_uuid(),
            'creator_user_uid' => $userCreator->uid,
            'course_status_uid' => CourseStatusesModel::where('code', 'PENDING_APPROVAL')->first()->uid,
        ]);

        // Simular los datos de entrada para el cambio de estado
        $changesCoursesStatuses = [
            [
                'uid' => $course->uid,
                'status' => 'ACCEPTED',
                'reason' => 'Test reason'
            ]
        ];

        // Fake para evitar el envío real de correos y manejo de la cola
        Queue::fake();

        // Crear la solicitud con los datos de entrada simulados
        $request = Request::create('/learning_objects/courses/change_statuses_courses', 'POST', [
            'changesCoursesStatuses' => $changesCoursesStatuses
        ]);


        // Create a mock for EmbeddingsService

        // Instanciar el controlador y ejecutar el método

        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService);
        $response = $controller->changeStatusesCourses($request);

        // Verificar que la respuesta sea la esperada
        $this->assertEquals(200, $response->status());
        $this->assertEquals('Se han actualizado los estados de los cursos correctamente', $response->getData()->message);

        // Verificar que el estado del curso se haya actualizado
        $course->refresh(); // Recargar el curso desde la base de datos
        $this->assertEquals('ACCEPTED', $course->status->code);

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


    // /** @test Guadar Curso */
    public function testSaveCourseCreatesNewCourse()
    {
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        $course_type =  CourseTypesModel::factory()->create()->first();
        $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();
        $center = CentersModel::factory()->create()->first();

        $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();


        // Crear un mock para general_options
        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'some_option_array' => [], // Asegúrate de que esto sea un array'
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'description'=> 'Porro et distinctio ab inventore sit',
                'validate_student_registrations' => 0,
                'educational_program_type_uid' => $educational_program_type->uid,
                'payment_mode' => 'SINGLE_PAYMENT',
            ]
        );
        // Convertir los tags a un JSON string
        $tags = json_encode([]); // '["tag1","tag2"]'

        // Asegúrate de que el JSON string sea válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error en la conversión JSON');
        }
        $datos_course = [
            'course_uid' => null,
            'action' => 'draft',
            'belongs_to_educational_program' => true,
            'title' => $course->title,
            'description'=> 'Porro et distinctio ab inventore sit',
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
            'contact_emails' => json_encode(['email1@email.com', 'email2@email.com']),
            'payment_mode' => $course->payment_mode,
            // 'tags' => $tags,
        ];

        $response = $this->postJson('/learning_objects/courses/save_course', $datos_course);

        $response->assertStatus(200);

        $response->assertJson(['message' => 'Se ha añadido el curso correctamente']);

        $this->assertDatabaseHas('courses',  [
            'creator_user_uid' => $user->uid,
            'course_status_uid' => $status->uid,
            // otros datos que quieras verificar
        ]);
    }

    // /** @test Guadar Curso con role MANAGEMENT */
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
            'some_option_array' => [], // Asegúrate de que esto sea un array'
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'validate_student_registrations' => 0,
                'educational_program_type_uid' => $educational_program_type->uid,
                'description'=> 'Porro et distinctio ab inventore sit',
                'featured_big_carrousel_description' => 'Harum facilis consequatur aut nesciunt ut esse aliquid'
                // 'course_status_uid' => $status->uid,
            ]
        );

        // Convertir los tags a un JSON string
        $tags = json_encode([]); // '["tag1","tag2"]'

        // Asegúrate de que el JSON string sea válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error en la conversión JSON');
        }

        $coursePayment = CoursesPaymentTermsModel::factory()->create([
            'course_uid' => $course->uid,
        ]);

        $ct=CoursesTagsModel::factory()->count(3)->create([
            'course_uid' => $course->uid,
        ]);

        CategoriesModel::factory()->count(3)->create();

        $learning = LearningResultsModel::factory()->withCompetence()->count(2)->create()->first();

        $datos_course = [
            'course_uid' => null,
            'action' => 'draft',
            'belongs_to_educational_program' => false,
            'title' => $course->title,
            'description'=> 'Porro et distinctio ab inventore sit',
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
            'tags'=> json_encode([
                'tag1','tag2'
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
            'payment_mode'=>'INSTALLMENT_PAYMENT',
        ]);

        $generalOptionsMock = [
            'operation_by_calls' => false,
            'necessary_approval_editions' => true,
            'some_option_array' => [],
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
            'payment_mode'=>'INSTALLMENT_PAYMENT',
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
    }

    /** @test Validacion al tratar de Guardar curso */
    public function testSaveCourseFailsWithValidationErrors()
    {
        $user = UsersModel::factory()->create();
        Auth::login($user);

        $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
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






    /**
     * @test Obtener curso sin CourseUid.
     */
    public function testGetCourseWithoutCourseUid()
    {
        $response = $this->get('/learning_objects/courses/get_course/');

        $response->assertStatus(404); // Since a missing UID will not match the route
    }

/**
 * @test
 * Este test verifica que, cuando un curso tiene un `course_origin_uid`,
 * se ejecuta la validación correspondiente para la edición del curso
 * y se manejan los errores de manera adecuada.
 */
    public function testSaveCourseWithCourseOriginUidValidatesCourseEdition()
    {

        $user = UsersModel::factory()->create();
        $roles = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $roles->uid => ['uid' => generate_uuid()]
        ]);
        $this->actingAs($user);

        // Mockear opciones generales
        App::instance('general_options', [
            'operation_by_calls' => false,
            'necessary_approval_editions' => true,
        ]);

        // Crear estados y otros modelos necesarios
        $editableStatus = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $call = CallsModel::factory()->create()->first();
        $lmsSystems = LmsSystemsModel::factory()->create()->first();

    $programStatus = EducationalProgramStatusesModel::where('code', 'INTRODUCTION')->first();
    // Crear un programa educativo con fechas válidas
    $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
        'realization_start_date' => Carbon::now()->addDays(61)->format('Y-m-d\TH:i'),
        'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
        'educational_program_status_uid' => $programStatus->uid,
    ])->first();

        // Crear curso original
        $originalCourse = CoursesModel::factory()->withCourseType()->create([
            'uid' => generate_uuid(),
            'course_status_uid' => $editableStatus->uid,
            'call_uid' => $call->uid,
            'lms_system_uid' => $lmsSystems->uid,
            'belongs_to_educational_program' => true,
            'educational_program_uid' => $educationalProgram->uid,
        ])->first();

        // Crear nuevo curso que referencia al curso original
        $newCourse = CoursesModel::factory()->withCourseType()->create([
            'course_origin_uid' => $originalCourse->uid,
            'course_status_uid' => $editableStatus->uid,
        ])->first();

    // Simular datos de solicitud
    $requestData = [
        'course_uid' => $newCourse->uid,
        'title' => 'Test Course Title', // Campo requerido
        'description' => 'Test Description',
        'contact_information' => 'Contact Info',
        'course_type_uid' => generate_uuid(),
        'educational_program_type_uid' => generate_uuid(),
        'min_required_students' => 10,
        'realization_start_date' => Carbon::now()->addDays(62)->format('Y-m-d'),
        'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d'),
        'validate_student_registrations' => true,
        'lms_url' => 'http://example.com',
        'lms_system_uid' => $lmsSystems->uid,

            // Campos requeridos si featured_big_carrousel es 1
            'featured_big_carrousel' => 1,
            'featured_big_carrousel_title' => 'Featured Title',
            'featured_big_carrousel_description' => 'Featured Description',

            // Estructura válida con menos de 100 resultados por bloque
            'structure' => json_encode([
                [
                    'learningResults' => array_fill(0, 99, ['result_description' => 'Learning result description']) // 99 resultados válidos
                ],
                [
                    'learningResults' => array_fill(0, 50, ['result_description' => 'Another learning result description']) // Otro bloque con 50 resultados válidos
                ]
            ]),

            // Emails de contacto válidos
            'contact_emails' => json_encode(['email1@example.com', 'email2@example.com']),

        // Otros campos opcionales según sea necesario
        // Ejemplo:
        //'payment_mode' => "SINGLE_PAYMENT",
        //'cost' => 100,
    ];

    $request = new Request($requestData);


        // Create a mock for EmbeddingsService
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

    // Instantiate ManagementCoursesController with the mocked service
    $controller = new ManagementCoursesController($mockEmbeddingsService);

    // Ejecutar el método del controlador como una solicitud JSON para obtener un código de estado 422 si hay errores de validación.
    $response = $controller->saveCourse($request);

        // Crear respuesta de prueba
        $response = $this->createTestResponse($response);

        // Captura de errores si no es 200
        if ($response->status() !== 200) {
            Log::error('Response errors:', $response->json());

            if (isset($response['errors'])) {
                Log::error('Validation errors:', $response['errors']);
            }

        // Aquí puedes agregar una aserción para verificar el código de estado 422 si es necesario.
        if ($response->status() === 422) {
            Log::info('Validation failed with errors:', $response['errors']);
            return; // Termina la ejecución si es un error esperado.
        }
    }

        // Verificar el código de estado y mensaje esperado
        $response->assertStatus(200);
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



    /**
     * @test Método getCourseStudents cuando no hay estudiantes.
     */
    public function testGetCourseStudentsNoStudents()
    {
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $response = $this->get('/learning_objects/courses/get_course_students/' . $course->uid);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'data' => [],
            ]);
    }
    /**
     * @test método getCourseStudents con parámetro de búsqueda.

     */
    public function testGetCourseStudentsWithSearch()
    {
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create()->first();

        $student = UsersModel::factory()->create([
            'nif' => '12345678X',
            "first_name" => "Manuel",
            "last_name" => "Hegmann",
        ])->first();

        $course->students()->attach($student->uid, [
            'uid' => generate_uuid(),  // Generar un UID para la relación en la tabla pivot
            'acceptance_status' => 'PENDING',
        ]);

        $response = $this->get('/learning_objects/courses/get_course_students/' . $course->uid . '?search=Manuel');

        $response->assertStatus(200);
    }

    /**
     * Test getCourseStudents method with sort parameter.
     */

     public function testOrdersStudentsSortParameter()
     {
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create()->first();

         UsersModel::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
         UsersModel::factory()->create(['first_name' => 'John', 'last_name' => 'Smith']);
         UsersModel::factory()->create(['first_name' => 'Alice', 'last_name' => 'Johnson']);
         $users = UsersModel::where('email','!=','admin@admin.com')->get();

         foreach ($users as $key => $user) {
            $course->students()->attach($user->uid, ['uid' => generate_uuid()]);
         }
           // Realizar la solicitud a la ruta correspondiente pasando los parámetros de consulta como un array
           $response = $this->get('/learning_objects/courses/get_course_students/'.$course->uid . '?sort[0][field]=first_name&sort[0][dir]=asc&size=3');

            // Verificar que la respuesta sea exitosa
            $response->assertStatus(200);

            // Obtener los estudiantes desde la respuesta y verificar el orden
            $studentsResponse = json_decode($response->getContent(), true);

            // Aserción para verificar que hay estudiantes en la respuesta
            $this->assertNotEmpty($studentsResponse['data'], 'La respuesta no debe estar vacía.');


      }



    /**
     * @test obtener el método de los estudiantes del curso con paginación.
     */
    public function testGetCourseStudentsWithPagination()
    {
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create()->first();

        $students = UsersModel::factory()->count(10)->create();

        $students->each(function ($student) use ($course) {
            $course->students()->attach($student->uid, [
                'uid' => \Illuminate\Support\Str::uuid(),
            ]);
        });
        // $course->students()->attach($students->pluck('id')->toArray());

        $response = $this->get('/learning_objects/courses/get_course_students/' . $course->uid . '?size=5');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 10,
                'per_page' => 5,
            ])
            ->assertJsonStructure([
                'total',
                'per_page',
                'current_page',
                'last_page',
                'data' => [
                    '*' => ['uid', 'first_name', 'last_name', 'nif'],
                ],
            ]);
    }

    /** Test Puede Aprobar Inscripciones Curso */
    public function testCanApproveInscriptionsCourse()
    {
        // Preparar
        Queue::fake();
        // Prepara los datos de prueba
        $student = CoursesStudentsModel::factory()->withCourse()->withUser()->create([
            'acceptance_status' => 'PENDING', // Estado inicial
        ])->first();

        $studentLast = CoursesStudentsModel::query()->latest()->first();

        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Simula la solicitud
        $response = $this->postJson('/learning_objects/courses/approve_inscriptions_course', [
            'uids' => [$studentLast->uid],
        ]);

        // Verifica la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Inscripciones aprobadas correctamente']);

        // Verifica que el estado de aceptación se haya actualizado
        $student->refresh();
        $this->assertEquals('ACCEPTED', $student->acceptance_status);
    }

    /** @test Puede Rechazar Inscripciones Curso */
    public function testCanRejectInscriptionsCourse()
    {
        // Preparar
        Queue::fake();
        // Prepara los datos de prueba
        $student = CoursesStudentsModel::factory()->withCourse()->withUser()->create([
            'acceptance_status' => 'PENDING', // Estado inicial
        ])->first();

        $studentLast = CoursesStudentsModel::query()->latest()->first();
        // dd($studentLast);

        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Simula la solicitud
        $response = $this->postJson('/learning_objects/courses/reject_inscriptions_course', [
            'uids' => [$studentLast->uid],
        ]);

        // Verifica la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Inscripciones rechazadas correctamente']);

        // Verifica que el estado de aceptación se haya actualizado
        $student->refresh();
        $this->assertEquals('REJECTED', $student->acceptance_status);

        // Verifica que se haya creado un log
        // Aquí puedes verificar la existencia del log según tu implementación
    }

    /** @test  Puede borrar inscripciones curso*/
    public function testCanDeleteInscriptionsCourse()
    {
        // Prepara los datos de prueba
        $student1 = CoursesStudentsModel::factory()->withCourse()->withUser()->create();

        $studentLast1 = $student1->latest()->first();

        // Verifica que los estudiantes existen en la base de datos
        $this->assertDatabaseHas('courses_students', ['uid' => $studentLast1->uid]);


        // Simula la solicitud
        $response = $this->postJson('/learning_objects/courses/delete_inscriptions_course', [
            'uids' => [$studentLast1->uid],
        ]);

        // Verifica la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Inscripciones eliminadas correctamente']);

        // Verifica que los estudiantes hayan sido eliminados de la base de datos
        $this->assertDatabaseMissing('courses_students', ['uid' => $studentLast1->uid]);
    }

    /** @test testCanDuplicateCourse */
    // Pendiente por resolver

    // public function testCanDuplicateCourse()
    // {

    //     // Prepara los datos de prueba
    //     $teachers = UsersModel::factory()->count(2)->create();
    //     $tags = CoursesTagsModel::factory()->count(2)->create();
    //     $categories = CategoriesModel::factory()->count(2)->create();

    //     // $blocks = BlocksModel::factory()->count(2)->create();

    //     // Asocia los profesores al curso
    //     $originalCourse = CoursesModel::factory()
    //     ->hasAttached($teachers, ['type' => 'COORDINATOR'], 'teachers')
    //     ->hasAttached($categories, [], 'categories')
    //     ->hasAttached($tags, [], 'tags')
    //     ->has(BlocksModel::factory()->count(2), 'blocks')
    //     ->has(CourseDocumentsModel::factory()->count(1), 'courseDocuments')
    //     ->create([
    //         'title' => 'Curso Original',
    //     ]);

    //     $introductionStatus = CourseStatusesModel::factory()->create([
    //         'code' => 'INTRODUCTION',
    //     ])->latest()->first();

    //     // dd($originalCourse->uid);
    //     // Simula la solicitud
    //     $response = $this->postJson('/learning_objects/courses/duplicate_course/' . $originalCourse->uid);

    //     // Verifica la respuesta
    //     $response->assertStatus(200)
    //         ->assertJson(['message' => 'Curso duplicado correctamente']);

    //     // Verifica que el curso duplicado exista en la base de datos
    //     $this->assertDatabaseHas('courses', [
    //         'title' => 'Curso Original (copia)',
    //         'course_status_uid' => $introductionStatus->uid,
    //     ]);

    //     // Verifica que el curso original no haya sido modificado
    //     $this->assertDatabaseHas('courses', [
    //         'uid' => $originalCourse->uid,
    //         'title' => 'Curso Original',
    //     ]);
    // }

    /** @test Edición Curso Éxito */
    public function testEditionCourseSuccess()
    {
        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Crear el estado de introducción
        $introductionStatus = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        // Crear un curso base en la base de datos
        $courseBd = CoursesModel::factory()->withCourseType()->create([
            'title' => 'Curso de prueba',
            'course_status_uid' => $introductionStatus->uid,
            'belongs_to_educational_program' => false,
        ])->latest()->first();


        // Simular la solicitud
        $response = $this->postJson('/learning_objects/courses/create_edition', [
            'course_uid' => $courseBd->uid,
        ]);

        // Verificar la respuesta HTTP
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Edición creada correctamente'
            ]);

        // Verificar que el nuevo curso fue creado
        $this->assertDatabaseHas('courses', [
            'title' => 'Curso de prueba (nueva edición)',
            'course_origin_uid' => $courseBd->uid,
            'course_status_uid' => $courseBd->course_status_uid,
        ]);
    }

    /** @test  puede obtener todas las competencias */
    public function testCanGetAllCompetences()
    {
        // Prepara el entorno de prueba creando datos de ejemplo
        $competence1 = CompetencesModel::factory()->create(['parent_competence_uid' => null])->first();
        $competence2 = CompetencesModel::factory()->create(['parent_competence_uid' => null])->latest()->first();


        // Realiza la solicitud a la ruta
        $response = $this->get('/learning_objects/courses/get_all_competences');

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

    }

    /** @test Puede inscribir a las estudiantes en el curso */
    public function testCanEnrollStudentsInACourse()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Crea usuarios y un curso
        $user1 = UsersModel::factory()->create();
        $user2 = UsersModel::factory()->create();
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $courseUid = $course->uid;
        $usersToEnroll = [
            $user1->uid,
            $user2->uid,
        ];

        // Asegúrate de que no hay inscripciones previas
        $this->assertDatabaseMissing('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user1->uid,
        ]);
        $this->assertDatabaseMissing('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user2->uid,
        ]);

        // Datos de solicitud
        $requestData = [
            'courseUid' => $courseUid,
            'usersToEnroll' => $usersToEnroll,
        ];

        // Realiza la solicitud POST a la ruta
        $response = $this->postJson('/learning_objects/courses/enroll_students', $requestData);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que el mensaje de respuesta sea el esperado
        $response->assertJson(['message' => 'Alumnos añadidos al curso']);

        // Verifica que los estudiantes se hayan inscrito correctamente
        foreach ($usersToEnroll as $userUid) {
            $this->assertDatabaseHas('courses_students', [
                'course_uid' => $courseUid,
                'user_uid' => $userUid,
                'calification_type' => 'NUMERIC',
                'acceptance_status' => 'ACCEPTED',
            ]);
        }
    }

    /** @test No inscribe a los estudiantes ya inscritos */
    public function testDoesNotEnrollAlreadyEnrolledStudents()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Crea usuarios y un curso
        $userAlreadyEnrolled = UsersModel::factory()->create();
        $newUser = UsersModel::factory()->create();
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $courseUid = $course->uid;

        // Inscribe previamente a un usuario
        CoursesStudentsModel::create([
            'uid' => generate_uuid(),
            'course_uid' => $courseUid,
            'user_uid' => $userAlreadyEnrolled->uid,
            'calification_type' => 'NUMERIC',
            'acceptance_status' => 'ACCEPTED',
        ]);

        // Datos de solicitud
        $requestData = [
            'courseUid' => $courseUid,
            'usersToEnroll' => [$userAlreadyEnrolled->uid, $newUser->uid],
        ];

        // Realiza la solicitud POST a la ruta
        $response = $this->postJson('/learning_objects/courses/enroll_students', $requestData);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que el mensaje de respuesta sea el esperado
        $response->assertJson(['message' => 'Alumnos añadidos al curso. Los ya registrados no se han añadido.']);

        // Verifica que el nuevo estudiante se haya inscrito correctamente
        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $newUser->uid,
            'calification_type' => 'NUMERIC',
            'acceptance_status' => 'ACCEPTED',
        ]);

        // Verifica que el estudiante ya inscrito no se haya duplicado
        $this->assertEquals(1, CoursesStudentsModel::where('course_uid', $courseUid)
            ->where('user_uid', $userAlreadyEnrolled->uid)
            ->count());
    }

    // /** @test Puede inscribir estudiantes desde csv */
    public function testCanEnrollStudentsFromCsv()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Crea dos usuarios y obtén sus datos
        $user1 = UsersModel::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nif' => '28632229N',
            'email' => 'john@example.com',
        ]);

        $user2 = UsersModel::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'nif' => '79987901L',
            'email' => 'jane@example.com',
        ]);

        // Simula un archivo CSV
        Storage::fake('local');

        $csvContent = "first_name,last_name,nif,email\n" .
            "John,Doe,28632229N,john@example.com\n" .
            "Jane,Smith,79987901L,jane@example.com";

        $csvFile = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        // Datos de solicitud
        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Realiza la solicitud POST a la ruta
        $response = $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que el mensaje de respuesta sea el esperado
        $response->assertJson(['message' => 'Alumnos añadidos al curso. Los ya registrados no se han añadido.']);

        // Verifica que los estudiantes se hayan inscrito correctamente
        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user1->uid,
            'calification_type' => 'NUMERIC',
            'acceptance_status' => 'ACCEPTED',
        ]);

        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user2->uid,
            'calification_type' => 'NUMERIC',
            'acceptance_status' => 'ACCEPTED',
        ]);
    }

    /** @test puede descargar un documento de estudiante*/
    public function testCanDownloadAStudentDocument()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Verifica y crea la estructura de directorios si es necesario
        Storage::disk('public')->makeDirectory('documents');

        // Copia el archivo a la ubicación pública de almacenamiento
        $sourceFilePath = public_path('document.pdf');
        $targetFilePath = storage_path('app/public/documents/document.pdf');

        if (file_exists($sourceFilePath)) {
            // Asegúrate de que el archivo se copie a la ubicación correcta
            copy($sourceFilePath, $targetFilePath);
        } else {
            $this->fail('El archivo source public/document.pdf no existe.');
        }

        // Verifica que el archivo se haya copiado correctamente
        $this->assertFileExists($targetFilePath, 'El archivo no se copió correctamente a la ruta esperada.');

        // Crea una entrada en la base de datos para el documento
        $document = CoursesStudentDocumentsModel::factory()->create([
            'document_path' => 'documents/document.pdf',  // Ruta relativa en el disco 'public'
        ])->first();

        // Datos de la solicitud
        $requestData = [
            'uidDocument' => $document->uid,
        ];

        // Realiza la solicitud POST a la ruta
        $response = $this->post('/learning_objects/courses/download_document_student', $requestData);

        // Verifica que la respuesta sea exitosa y que se descarga el archivo correcto
        $response->assertStatus(200);
        $response->assertDownload('document.pdf');
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

        // Create a mock for EmbeddingsService

        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService);

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
}
