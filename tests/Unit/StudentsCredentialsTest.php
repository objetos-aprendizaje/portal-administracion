<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramsModel;
use App\Models\CertidigitalAssesmentsModel;
use App\Models\CertidigitalCredentialsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\EducationalProgramsStudentsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;


class StudentsCredentialsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @testdox Obtener Index View Estudiantes
     */

    public function testIndexViewStudents()
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

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);
        // Realizar la solicitud a la ruta
        $response = $this->get(route('credentials-students'));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertViewIs('credentials.students.index');
        $response->assertViewHas('page_name', 'Credenciales de estudiantes');
        $response->assertViewHas('page_title', 'Credenciales de estudiantes');
        $response->assertViewHas('resources', [
            "resources/js/credentials_module/students_credentials.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'credentials-students');
    }


    /**
     * @testdox Obtener estudiantes
     */
    public function testGetStudentsWithPagination()
    {

        $students = UsersModel::factory()->count(2)->create();

        // Asigna el rol 'STUDENT' a los usuarios creados
        foreach ($students as $student) {
            $student->roles()->attach(UserRolesModel::where('code', 'STUDENT')->first()->uid, [
                'uid' => generateUuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Simula que el usuario está autenticado
        $this->actingAs($students->first());

        // Realiza la solicitud a la ruta
        $response = $this->get('/credentials/students/get_students?size=2');

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ],
            'last_page',
            'per_page',
            'total',
        ]);

        // Verifica que la paginación funcione
        $this->assertCount(2, $response->json('data'));
    }

    /** @test  Obtener cursos por estudiante con paginación*/
    public function testGetCoursesStudents()
    {
        // Crea un estudiante de prueba
        $student = UsersModel::factory()->create()->first();

        // Crea algunos cursos y asocia al estudiante
        $courses = CoursesModel::factory()->withCourseStatus()->withCourseType()->count(5)->create();
        $pivot_data = [];

        foreach ($courses as $course) {
            $pivot_data[] = [
                'uid' => generateUuid(),
                'course_uid' => $course->uid,
                'user_uid' => $student->uid,
            ];
        }

        $student->coursesStudents()->sync($pivot_data);

        // Realiza la solicitud a la ruta
        $response = $this->get('/credentials/students/get_courses_student/' . $student->uid . '?size=2');

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid', // Asegúrate de que 'uid' existe en el modelo de cursos
                    'title',
                    // Agrega otras propiedades del curso que esperas
                ],
            ],
            'last_page',
            'per_page',
            'total',
        ]);

        // Verifica que la paginación funcione
        $this->assertCount(2, $response->json('data'));
    }


    /** @test  Busca curso por estudiante*/
    public function testSearchCoursesForStudent()
    {
        // Crea un estudiante de prueba
        $student = UsersModel::factory()->create()->first();

        // Crea algunos cursos
        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Mathematics']);
        $course2 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Science']);

        // Crea los datos de la relación con uid
        $pivotData = [
            [
                'uid' => generateUuid(),
                'course_uid' => $course1->uid,
                'user_uid' => $student->uid,
            ],
            [
                'uid' => generateUuid(),
                'course_uid' => $course2->uid,
                'user_uid' => $student->uid,
            ],
        ];

        // Inserta los datos en la tabla intermedia
        DB::table('courses_students')->insert($pivotData);

        // Realiza la solicitud a la ruta con un parámetro de búsqueda
        $response = $this->get('/credentials/students/get_courses_student/' . $student->uid . '?search=Math');

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Mathematics']);
        $response->assertJsonMissing(['title' => 'Science']);
    }


    /** @test Obtener Credenciales de Estudiante mediante búsqueda*/
    public function testSearchCredentialsStudents()
    {
        // Crea un estudiante en la base de datos
        UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Ana', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com']);

        // Act: Realiza una solicitud GET con el parámetro de búsqueda
        $response = $this->get('/credentials/students/get_students?search=John');

        // Assert: Verifica que la respuesta contenga el estudiante buscado
        $response->assertStatus(200);
    }

    /** @test Ordena credenciales Estudiantes*/

    public function testSortCredentialsStudents()
    {
        // Crea estudiantes en la base de datos
        $student1 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'John', 'last_name' => 'Perez', 'email' => 'john@example.com']);
        $roles = UserRolesModel::firstOrCreate(['code' => 'STUDENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $student1->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        $student2 = UsersModel::factory()->create(['uid' => generateUuid(), 'first_name' => 'Smith', 'last_name' => 'Alvarez', 'email' => 'smith@example.com']);
        $student2->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Act: Realiza una solicitud GET con parámetros de ordenación
        $response = $this->get('/credentials/students/get_students?sort[0][field]=last_name&sort[0][dir]=asc&size=2');


        $response->assertStatus(200);
    }

    /** @test Ordena estudiantes de cursos por credenciales*/
    public function testSortCoursesStudentsByCredential()
    {
        // Arrange: Crea un estudiante y cursos en la base de datos
        $student = UsersModel::factory()->create();

        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['uid' => generateUuid(), 'title' => 'Course 1']);
        $course2 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['uid' => generateUuid(), 'title' => 'Course 2']);

        // Asocia los cursos al estudiante con diferentes credeciales
        $student->coursesStudents()->attach($course1->uid, ['uid' => generateUuid(), 'credential' => 'Certificate']);
        $student->coursesStudents()->attach($course2->uid, ['uid' => generateUuid(), 'credential' => 'Diploma']);

        // Act: Realiza una solicitud GET con parámetros de ordenación
        $response = $this->get("/credentials/students/get_courses_student/{$student->uid}?sort[0][field]=title&sort[0][dir]=asc&size=2");

        // Assert: Verifica que la respuesta contenga un estado 200
        $response->assertStatus(200);
        $data = $response->json();

        // Verifica que haya datos en la respuesta
        $this->assertNotEmpty($data['data'], 'No se encontraron cursos del estudiante en la respuesta');
    }

    /**
     * @test No se pueden emitir credenciales para cursos ya procesados
     */
    public function testEmitCredentialsFailsForAlreadyEmittedCourses()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        $courses = CoursesModel::factory()->withCourseStatus()->count(2)->withCourseType()->create();

        foreach ($courses as $course) {
            CoursesStudentsModel::factory()->create([
                'user_uid' => $user->uid,
                'course_uid' => $course->uid,
                'emissions_block_uuid' => generateUuid(),
            ]);
        }

        // Realizar la solicitud
        $response = $this->postJson(route('emit-credentials'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid
        ]);

        // Verificar respuesta
        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'No se pueden emitir credenciales porque alguno de los cursos ya tiene credenciales emitidas',
        ]);
    }

     /**
     * @test No se pueden emitir credenciales para cursos sin credenciales asociadas
     */
    public function testEmitCredentialsFailsForNotEmittedCourses()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        $courses = CoursesModel::factory()->withCourseStatus()->count(2)->withCourseType()->create();

        foreach ($courses as $course) {
            CoursesStudentsModel::factory()->create([
                'user_uid' => $user->uid,
                'course_uid' => $course->uid,
            ]);
        }

        // Realizar la solicitud
        $response = $this->postJson(route('emit-credentials'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid
        ]);

        // Verificar respuesta
        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'No se pueden emitir credenciales porque alguno de los cursos no tiene credenciales asociadas',
        ]);
    }

    /**
     * @test No se pueden emitir credenciales para cursos ya procesados
     */
    public function testSendCredentialsSuccessfully()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

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

        $certidCredencial = CertidigitalCredentialsModel::factory()->create()->first();

        $courses = CoursesModel::factory()->withCourseStatus()->withCourseType()->count(2)->create();

        foreach ($courses as $course) {

            CoursesStudentsModel::factory()->create([
                'user_uid' => $user->uid,
                'course_uid' => $course->uid,
                'emissions_block_uuid' => $certidCredencial->uid
            ]);
        }

        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create();

        $educationalProgramStudent = EducationalProgramsStudentsModel::factory()->create(
            [
                'user_uid' => $user->uid,
                'educational_program_uid' => $educationalProgram->uid,
                'emissions_block_uuid' => $certidCredencial->uid
            ]
        );

        $response = $this->postJson(route('send-credentials'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
            'educational_programs' => [$educationalProgramStudent->uid]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Credenciales enviadas correctamente',
        ]);
    }

    /**
     * @test No se pueden enviar sino existe credencial emitida
     */
    public function testSendCredentialsFails()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);


        $courses = CoursesModel::factory()->withCourseStatus()->withCourseType()->count(2)->create();

        foreach ($courses as $course) {

            CoursesStudentsModel::factory()->create([
                'user_uid' => $user->uid,
                'course_uid' => $course->uid,
                'emissions_block_uuid' => null
            ]);
        }

        $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create();

        $educationalProgramStudent = EducationalProgramsStudentsModel::factory()->create(
            [
                'user_uid' => $user->uid,
                'educational_program_uid' => $educationalProgram->uid,
                'emissions_block_uuid' => null
            ]
        );

        $response = $this->postJson(route('send-credentials'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
            'educational_programs' => [$educationalProgramStudent->uid]
        ]);

        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'No se pueden enviar credenciales porque alguno de los cursos o programas formativos no las tiene emitidas',
        ]);
    }


    /**
     * @test No se puede Sellar Credenciales por error 406
     */
    public function testSealCredentialsFails()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        $courses = CoursesModel::factory()->withCourseStatus()->withCourseType()->count(2)->create();

        foreach ($courses as $course) {

            CoursesStudentsModel::factory()->create([
                'user_uid' => $user->uid,
                'course_uid' => $course->uid,
            ]);
        }

        $educationalPrograms = EducationalProgramsModel::factory()->count(2)->withEducationalProgramType()->create();

        foreach ($educationalPrograms as  $educationalProgram) {
            $educationalProgram->students()->attach($user->uid, [
                'uid' => generateUuid(),
                'acceptance_status' => 'PENDING',
            ]);
        }

        $response = $this->postJson(route('seal-credentials'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
            'educational_programs' => [$educationalPrograms[0]->uid, $educationalPrograms[1]->uid]
        ]);

        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'No se pueden sellar credenciales porque alguno de los objetos de aprendizaje no las tiene emitidas',
        ]);
    }
}
