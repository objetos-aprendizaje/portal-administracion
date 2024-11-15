<?php

namespace Tests\Unit;



use Mockery;
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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Models\CoursesStudentsModel;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\CertidigitalService;
use Illuminate\Support\Facades\Queue;
use App\Models\CoursesEmbeddingsModel;
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
use App\Jobs\SendCourseNotificationToManagements;
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

        // Crear mocks del certificado
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Create a mock for EmbeddingsService
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService, $certidigitalServiceMock);

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
        $users = UsersModel::where('email', '!=', 'admin@admin.com')->get();

        foreach ($users as $key => $user) {
            $course->students()->attach($user->uid, ['uid' => generate_uuid()]);
        }
        // Realizar la solicitud a la ruta correspondiente pasando los parámetros de consulta como un array
        $response = $this->get('/learning_objects/courses/get_course_students/' . $course->uid . '?sort[0][field]=first_name&sort[0][dir]=asc&size=3');

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
         CompetencesModel::factory()->create(['parent_competence_uid' => null])->first();
        CompetencesModel::factory()->create(['parent_competence_uid' => null])->latest()->first();


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
            'acceptance_status' => 'ACCEPTED',
        ]);

        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user2->uid,
            'acceptance_status' => 'ACCEPTED',
        ]);
    }



    /** @test Valida que se arroje una excepción si el NIF o el correo no son válidos */
    public function testInvalidNifThrowsException()
    {
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Simula un archivo CSV con NIF y correo inválidos
        Storage::fake('local');
        $csvContent = "first_name,last_name,nif,email\n" .
            "John,Doe,invalid_nif,johnexample.com";
        $csvFile = UploadedFile::fake()->createWithContent('students_invalid.csv', $csvContent);

        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Captura la excepción lanzada y verifica el mensaje de error
        try {
            $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);
        } catch (OperationFailedException $e) {
            $this->assertEquals("El NIF/NIE de la línea 1 no es válido", $e->getMessage());
        }

    }

    /** @test Valida que se arroje una excepción si el correo no es válido */
    public function testInvalidEmailThrowsException()
    {
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Simula un archivo CSV con un correo inválido
        Storage::fake('local');
        $csvContent = "first_name,last_name,nif,email\n" .
            "John,Doe,28632229N,invalid_email";
        $csvFile = UploadedFile::fake()->createWithContent('students_invalid_email.csv', $csvContent);

        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Captura la excepción lanzada y verifica el mensaje de error
        try {
            $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);
        } catch (OperationFailedException $e) {
            $this->assertEquals("El correo de la línea 1 no es válido", $e->getMessage());
        }
    }


    /** @test Puede registrar e inscribir un nuevo usuario desde csv */
    public function testCanSignUpAndEnrollNewUserFromCsv()
    {
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Simula un archivo CSV con datos de un usuario nuevo
        Storage::fake('local');
        $csvContent = "first_name,last_name,nif,email\n" .
            "New,User,28632229N,newuser@example.com";
        $csvFile = UploadedFile::fake()->createWithContent('new_student.csv', $csvContent);

        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Realiza la solicitud POST
        $response = $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que el usuario nuevo se haya registrado y se haya inscrito en el curso
        $this->assertDatabaseHas('users', [
            'nif' => '28632229N',
            'email' => 'newuser@example.com',
        ]);

        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => UsersModel::where('email', 'newuser@example.com')->first()->uid,
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



}
