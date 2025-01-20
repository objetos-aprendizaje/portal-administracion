<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use App\Models\CoursesTeachersModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\CertidigitalCredentialsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeachersCredentialsTest extends TestCase
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

    /** @test Obtener Index View Teachers*/
    public function testIndexViewTeachers()
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
        $response = $this->get(route('credentials-teachers'));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertViewIs('credentials.teachers.index');
        $response->assertViewHas('page_name', 'Credenciales de profesores');
        $response->assertViewHas('page_title', 'Credenciales de profesores');
        $response->assertViewHas('resources', [
            "resources/js/credentials_module/teachers_credentials.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'credentials-teachers');
    }

    /** @test Ordenar cursos por Teacher*/
    public function testSortTeachers()
    {
        $teacher1 = UsersModel::factory()->create(['first_name' => 'Alice']);
        $teacher2 = UsersModel::factory()->create(['first_name' => 'Bob']);

        // Asigna el rol 'TEACHER' a los usuarios creados
        $teacher1->roles()->attach(UserRolesModel::where('code', 'TEACHER')->first()->uid, [
            'uid' => generateUuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacher2->roles()->attach(UserRolesModel::where('code', 'TEACHER')->first()->uid, [
            'uid' => generateUuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simula que el usuario está autenticado
        $this->actingAs($teacher1);

        // Realiza la solicitud a la ruta con un parámetro de ordenamiento
        $response = $this->get('/credentials/teachers/get_teachers?sort[0][field]=first_name&sort[0][dir]=asc&size=10');

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $teachers = $response->json('data');

        // Verifica que el primer profesor sea 'Alice'
        $this->assertEquals('Alice', $teachers[0]['first_name']);
    }


    /** @test */
    public function testGetCoursesForATeacherWithPagination()
    {
        $teacher = UsersModel::factory()->create(['first_name' => 'Bob']);

        // Asigna el rol 'TEACHER' a los usuarios creados
        $teacher->roles()->attach(UserRolesModel::where('code', 'TEACHER')->first()->uid, [
            'uid' => generateUuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crea algunos cursos y asocia al profesor
        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Mathematics']);
        $course2 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Science']);

        // Crea los datos de la relación con uid
        $pivotData = [
            [
                'uid' => generateUuid(), // Genera un UID para la relación
                'course_uid' => $course1->uid,
                'user_uid' => $teacher->uid,
            ],
            [
                'uid' => generateUuid(), // Genera un UID para la relación
                'course_uid' => $course2->uid,
                'user_uid' => $teacher->uid,
            ],
        ];

        // Inserta los datos en la tabla intermedia
        DB::table('courses_teachers')->insert($pivotData);

        // Realiza la solicitud a la ruta
        $response = $this->get('/credentials/teachers/get_courses_teacher/' . $teacher->uid . '?size=2');

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'title',

                ],
            ],
            'last_page',
            'per_page',
            'total',
        ]);

        // Verifica que la paginación funcione
        $this->assertCount(2, $response->json('data'));
    }

    /** @test Obtenet profesor get_courses_student*/
    public function testSearchTeachersByFirstname()
    {

        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generateUuid()]);
        // Arrange: Crea dos profesores en la base de datos
        $teacher1 = UsersModel::factory()->create([
            'first_name' => 'Martha',
            'last_name' => 'Smith',
            'email' => 'alice@example.com'
        ]);
        $teacher1->roles()->attach($roles->uid, ['uid' => generateUuid()]);


        $teacher2 = UsersModel::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@example.com'
        ]);
        $teacher2->roles()->attach($roles->uid, ['uid' => generateUuid()]);


        // Act: Realiza una solicitud GET con el parámetro de búsqueda
        $response = $this->get('/credentials/teachers/get_teachers?search=Martha');

        // Assert: Verifica que la respuesta contenga el estado 200
        $response->assertStatus(200);
        $data = $response->json();

        // Verifica que solo haya un profesor en la respuesta
        $this->assertCount(1, $data['data'], 'Se esperaban 1 profesor, pero se encontraron ' . count($data['data']));
    }

    /** @test GetCoursesTeacher Obtiene cursos por titulo*/
    public function testSearchCoursesByTeacherByTitle()
    {
        // Arrange: Crea un profesor y cursos en la base de datos
        $teacher = UsersModel::factory()->create([
            'uid' => generateUuid()
        ]);

        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Physical']);
        $course2 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Science']);

        // Asocia los cursos al profesor
        $teacher->coursesTeachers()->attach($course1->uid, ['uid' => generateUuid()]);
        $teacher->coursesTeachers()->attach($course2->uid, ['uid' => generateUuid()]);

        // Act: Realiza una solicitud GET con el parámetro de búsqueda
        $response = $this->get("/credentials/teachers/get_courses_teacher/{$teacher->uid}?search=Physi");

        // Assert: Verifica que la respuesta contenga el estado 200
        $response->assertStatus(200);
        $data = $response->json();

        // Verifica que solo haya un curso en la respuesta
        $this->assertCount(1, $data['data'], 'Se esperaban 1 curso, pero se encontraron ' . count($data['data']));
    }

    /** @test GetCoursesTeacher Ordenar cursos de profesores*/
    public function testSortCoursesTeachersByTitle()
    {
        // Arrange: Crea un profesor y cursos en la base de datos
        $teacher = UsersModel::factory()->create([
            'uid' => generateUuid()
        ]);

        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Biology']);
        $course2 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['title' => 'Algebra']);

        // Asocia los cursos al profesor
        $teacher->coursesTeachers()->attach($course1->uid, ['uid' => generateUuid()]);
        $teacher->coursesTeachers()->attach($course2->uid, ['uid' => generateUuid()]);

        // Act: Realiza una solicitud GET con parámetros de ordenación
        $response = $this->get("/credentials/teachers/get_courses_teacher/{$teacher->uid}?sort[0][field]=title&sort[0][dir]=asc&size=2");

        // Assert: Verifica que la respuesta contenga el estado 200
        $response->assertStatus(200);
        $data = $response->json();

        // Verifica que los cursos estén ordenados por título
        $this->assertEquals('Algebra', $data['data'][0]['title']);
        $this->assertEquals('Biology', $data['data'][1]['title']);
    }



    public function testEmitCredentialsTeacherWithFailsCredencialExist()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);


        //::::: Cuando $coursesTeachersWithEmissions existe ::::://

        $certidCredencial = CertidigitalCredentialsModel::factory()->create()->first();

        $courses = CoursesModel::factory()->withCourseStatus()->withCourseType()->count(2)->create(
            // [
            //     'certidigital_teacher_credential_uid' => $certidCredencial->uid
            // ]
        );

        foreach ($courses as $course) {

            CoursesTeachersModel::factory()->create(
                [
                    'course_uid' => $course->uid,
                    'user_uid' => $user->uid,
                    'emissions_block_uuid' => $certidCredencial->uid
                ]
            );
        }

        $response = $this->postJson(route('emit-credentials-teacher'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
        ]);

        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'El profesor ya tiene credenciales emitidas para alguno de los cursos seleccionados',
        ]);
    }

    public function testEmitCredentialsTeacherWithFailsWithoutCredential()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);


        CertidigitalCredentialsModel::factory()->create()->first();


        $courses = CoursesModel::factory()->withCourseStatus()->withCourseType()->count(2)->create(
            [
                // 'certidigital_teacher_credential_uid' => $certidCredencial->uid
            ]
        );

        foreach ($courses as $course) {

            CoursesTeachersModel::factory()->create(
                [
                    'course_uid' => $course->uid,
                    'user_uid' => $user->uid,
                ]
            );
        }

        $response = $this->postJson(route('emit-credentials-teacher'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
        ]);

        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'No se pueden emitir credenciales porque alguno de los cursos no tiene credenciales asociadas',
        ]);
    }

    public function testSealCredentialsTeacherWithFail()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        $courses = CoursesModel::factory()->count(2)->withCourseStatus()->withCourseType()->create();

        foreach ($courses as $course) {
            $course->teachers()->attach($user->uid, [
                'uid' => generateUuid(),
            ]);
        }

        $response = $this->postJson(route('seal-credentials-teacher'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
        ]);

        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'El profesor no tiene credenciales emitidas para alguno de los cursos seleccionados',
        ]);
    }

    public function testSendCredentialsTeacherSuccessfully()
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

        $courses = CoursesModel::factory()->count(2)->withCourseStatus()->withCourseType()->create();

        $certidCredencial = CertidigitalCredentialsModel::factory()->create()->first();

        foreach ($courses as $course) {
            $course->teachers()->attach($user->uid, [
                'uid' => generateUuid(),
                'emissions_block_uuid' => $certidCredencial->uid
            ]);
        }

        $response = $this->postJson(route('send-credentials-teacher'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Credenciales enviadas correctamente',
        ]);
    }

    public function testSendCredentialsTeacherWithFail()
    {
        $user = UsersModel::where('email', 'admin@admin.com')->first();
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        Auth::login($user);

        $courses = CoursesModel::factory()->count(2)->withCourseStatus()->withCourseType()->create();

        foreach ($courses as $course) {
            $course->teachers()->attach($user->uid, [
                'uid' => generateUuid(),
            ]);
        }

        $response = $this->postJson(route('send-credentials-teacher'), [
            'courses' => [$courses[0]->uid, $courses[1]->uid],
            'user_uid' => $user->uid,
        ]);

        $response->assertStatus(406);
        $response->assertJson([
            'message' => 'El profesor no tiene credenciales emitidas para alguno de los cursos seleccionados',
        ]);
    }
}
