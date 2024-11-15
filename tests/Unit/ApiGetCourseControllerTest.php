<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\ApiKeysModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use App\Models\CoursesStudentsModel;
use App\Models\CoursesTeachersModel;
use Illuminate\Support\Facades\View;
use App\Models\EducationalProgramsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiGetCourseControllerTest extends TestCase
{
    use RefreshDatabase;


    /**
     * @test  Verifica que se maneja correctamente cuando el curso no es encontrado.
     */
    // public function testCourseNotFoundApiGetCourse()
    // {
    //     $admin = UsersModel::factory()->create();

    //     $this->actingAs($admin);

    //     // Datos de para genera la key de la api
    //     $apikey = ApiKeysModel::factory()->create()->first();

    //     $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

    //     $uid = Str::uuid();
    //     // Realizar la solicitud GET con un `course_lms_uid` que no existe
    //     $response = $this->getJson('/api/courses/' ,[
    //         'API-KEY' => $apikey->api_key
    //     ]);

    //     // Verificar que la respuesta sea 404 (Not Found)
    //     $response->assertStatus(404);
    //     $response->assertJson(['message' => 'Curso no encontrado']);
    // }

    /**
     * @test
     * Prueba que la API de cursos devuelve los datos correctamente con los filtros aplicados.
     */
    public function testGetCoursesWithFilters()
    {
        // Crear los datos de prueba
        $status = CourseStatusesModel::factory()->create(['code' => 'ACTIVE']);
        $teacher = UsersModel::factory()->create();
        $student = UsersModel::factory()->create();
        $center = CentersModel::factory()->create()->first();


        // Crear un curso con relaciones
        $course = CoursesModel::factory()
            ->withCourseType()
            // ->hasAttached($student, ['acceptance_status' => 'ACCEPTED', 'status' => 'ENROLLED'], 'students')
            ->create([
                'course_status_uid' => $status->uid,
                'course_lms_id' => 'LMS123',
                'center_uid' => $center->uid,
            ])->first();

        $course->students()->attach($student->uid, [
            'uid' => generate_uuid(),
            'acceptance_status' => 'ACCEPTED',
            'status' => 'ENROLLED'
        ]);

        CoursesTeachersModel::factory()->create(
            [
                'course_uid' => $course->uid,
                'user_uid' =>  $student->uid
            ]
        );

        // Generar el filtro
        $filters = [
            'uid' => $course->uid,
            'course_lms_id' => 'LMS123',
            'status' => ['ACTIVE'],
        ];

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        // Convertir los filtros en una cadena de consulta
        $queryString = http_build_query($filters);

        // Hacer la solicitud GET a la ruta con los filtros
        $response = $this->getJson(
            '/api/courses?' . $queryString,
            [
                'API-KEY' => $apikey->api_key
            ]
        );

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que los datos retornados son correctos
        $response->assertJsonFragment([
            'uid' => $course->uid,
            'course_lms_id' => 'LMS123',
            'status' => 'ACTIVE',
            'title' => $course->title,
            'description' => $course->description,
        ]);

        // Verificar que el profesor está asociado al curso
        // $response->assertJsonFragment([
        //     'teachers' => [$teacher->uid],
        // ]);

        // Verificar que el estudiante está asociado al curso y tiene el estado correcto
        $response->assertJsonFragment([
            'uid' => $student->uid,
            'email' => $student->email,
            'acceptance_status' => 'ACCEPTED',
            'status' => 'ENROLLED',
        ]);
    }


    /**
     * @test
     * Prueba que se valida y confirma la creación de un curso correctamente.
     */
    public function testConfirmCourseCreationWithValidations()
    {

        $status = CourseStatusesModel::where('code', 'ACCEPTED')->first();

        $course = CoursesModel::factory()
            ->withCourseType()
            ->create([
                "title" => "Curso de Prueba",
                'description' => 'Descripción del curso de prueba',
                'course_status_uid' => $status->uid,
                'course_lms_id' => 'LMS123', // Simulamos un curso con el mismo LMS ID para validar el unique
            ])->first();

        // Crear un curso de prueba con los datos requeridos
        $courseData = [
            'course_lms_id' => 'LMS123',
            'course_status_uid' => $status->uid,
            'poa_uid' => $course->uid, // Asegurarse de que este UID exista en la base de datos
            'lms_url' => 'https://valid-url.com',
            'title' => 'Curso de Prueba',
            'description' => 'Descripción del curso de prueba',
            // 'status' => 'CREATED',
            'teachers' => [generate_uuid()],
            'students' => [generate_uuid()],
        ];

        // Crear un curso existente para probar la validación de unique
        CoursesModel::factory()
            ->withCourseType()
            ->create([
                "title" => "Curso de Prueba",
                'description' => 'Descripción del curso de prueba',
                'course_lms_id' => 'LMS123', // Simulamos un curso con el mismo LMS ID para validar el unique
                'course_status_uid' => $status->uid
            ]);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        // Simular la solicitud POST con los datos del curso
        $response = $this->postJson('/api/courses/confirm_course_creation', $courseData, [
            'API-KEY' => $apikey->api_key,
        ]);

        // Verificar que la respuesta sea 400 (Bad Request) debido a la validación fallida
        $response->assertStatus(400);

        // Verificar que la respuesta contenga el mensaje de error por duplicación de course_lms_id
        $response->assertJsonValidationErrors(['course_lms_id' => 'Ya existe un curso con el uid de LMS proporcionado']);

        // Actualizar el course_lms_id para que sea único y la validación pase
        $courseData['course_lms_id'] = 'LMS124';

        // Hacer la solicitud POST nuevamente con los datos válidos
        $response = $this->postJson('/api/courses/confirm_course_creation', $courseData, [
            'API-KEY' => $apikey->api_key,
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Curso confirmado correctamente']);

        // Verificar que el curso fue guardado en la base de datos correctamente
        $this->assertDatabaseHas('courses', [
            'course_lms_id' => 'LMS124',
            'title' => 'Curso de Prueba',
            'description' => 'Descripción del curso de prueba',
            // 'status' => 'CREATED',
        ]);
    }


    /**
     * @test
     * Prueba que se actualiza un curso correctamente con profesores y estudiantes.
     */
    public function testUpdateCourseWithTeachersAndStudents()
    {
        $educationalProgram = EducationalProgramsModel::factory()
        ->withEducationalProgramType()
        ->create([
            'validate_student_registrations'=> 1,
            'enrolling_finish_date'=> now(),
            'inscription_start_date' => now()->subMonths(4),
            'inscription_finish_date' => now()->subMonths(3),
            'enrolling_start_date' => now()->subMonths(2),
            'enrolling_finish_date' => now()->subMonths(1),
            'realization_start_date' => now()->subMonth(),
            'realization_finish_date' => now()->addMonth(),
        ])->first();

        // Crear un curso existente
        $course = CoursesModel::factory()
        ->withCourseStatus()
        ->withCourseType()
        ->create([
            'course_lms_id' => 'LMS123',
            'educational_program_uid'=> $educationalProgram->uid,
            'inscription_start_date' => now()->subMonths(4),
            'inscription_finish_date' => now()->subMonths(3),
            'enrolling_start_date' => now()->subMonths(2),
            'enrolling_finish_date' => now()->subMonths(1),

        ])->first();

        // Crear profesores y estudiantes
        $teacher1 = UsersModel::factory()->create();
        $teacher2 = UsersModel::factory()->create();
        $student1 = UsersModel::factory()->create();
        $student2 = UsersModel::factory()->create();

        // Asignar roles a profesores y estudiantes
        $teacherRole = UserRolesModel::factory()->create(['code' => 'TEACHER']);
        $studentRole = UserRolesModel::factory()->create(['code' => 'STUDENT']);

        $teacher1->roles()->attach($teacherRole->uid,[
            'uid'=>generate_uuid(),
        ]);
        $teacher2->roles()->attach($teacherRole->uid,[
            'uid'=>generate_uuid(),
        ]);
        $student1->roles()->attach($studentRole->uid,[
            'uid'=>generate_uuid(),
        ]);
        $student2->roles()->attach($studentRole->uid,[
            'uid'=>generate_uuid(),
        ]);

        // Datos de la solicitud de actualización
        $updateData = [
            'title' => 'Curso Actualizado',
            'description' => 'Descripción actualizada del curso',
            'teachers' => [
                'coordinator' => [$teacher1->uid],
                'no_coordinator' => [$teacher2->uid]
            ],
            'students' => [$student1->uid, $student2->uid],
            'lms_url' => 'https://example.com',
            'ects_workload' => 6,
            'validate_student_registrations'=>1,
            'realization_start_date' => now()->subMonth(),
            'realization_finish_date' => now()->addMonth(),
        ];

        // Crear la API Key para la solicitud
        $apikey = ApiKeysModel::factory()->create()->first();

        // Realizar la solicitud POST con los datos de actualización del curso
        $response = $this->postJson("/api/courses/update/{$course->course_lms_id}", $updateData, [
            'API-KEY' => $apikey->api_key,
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Curso actualizado correctamente']);

        // Verificar que el curso fue actualizado en la base de datos
        $this->assertDatabaseHas('courses', [
            'course_lms_id' => $course->course_lms_id,
            'title' => 'Curso Actualizado',
            'description' => 'Descripción actualizada del curso',
            'lms_url' => 'https://example.com',
            'ects_workload' => 6,
            // 'validate_student_registrations'=>0,
            // 'realization_start_date' => now()->subMonth(),
            // 'realization_finish_date' => now()->addMonth(),
        ]);

        // Verificar que los profesores fueron actualizados correctamente
        $this->assertDatabaseHas('courses_teachers', [
            'course_uid' => $course->uid,
            'user_uid' => $teacher1->uid,
            'type' => 'COORDINATOR',
        ]);

        $this->assertDatabaseHas('courses_teachers', [
            'course_uid' => $course->uid,
            'user_uid' => $teacher2->uid,
            'type' => 'NO_COORDINATOR',
        ]);

        // Verificar que los estudiantes fueron actualizados correctamente
        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $course->uid,
            'user_uid' => $student1->uid,
        ]);

        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $course->uid,
            'user_uid' => $student2->uid,
        ]);
    }


    // /**
    //  * @test  Verifica que se puede obtener un curso correctamente y pertenece al programa educativo.
    //  */
    // public function testGetCourseSuccessfullyApiGetCourse()
    // {
    //     // Crea un usuario y actúa como él
    //     $admin = UsersModel::factory()->create();
    //     $this->actingAs($admin);

    //     // Datos de para genera la key de la api
    //     $apikey = ApiKeysModel::factory()->create()->first();

    //     $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);
    //     // Crea un usuario y actúa como él
    //     $admin = UsersModel::factory()->create();
    //     $this->actingAs($admin);

    //     // Simular la carga de datos que haría el GeneralOptionsMiddleware
    //     $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
    //     View::share('general_options', $general_options);

    //     // Datos de para genera la key de la api
    //     $apikey = ApiKeysModel::factory()->create()->first();

    //     $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

    //     $educationalProg = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->first();

    //     // Crear un curso de prueba con datos relacionados
    //     $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
    //         'course_lms_uid' => generate_uuid(),
    //         'title' => 'Curso de prueba',
    //         'description' => 'Descripción del curso de prueba',
    //         'ects_workload' => 10,
    //         'educational_program_uid' => $educationalProg->uid,
    //         'belongs_to_educational_program' => true,
    //         'lms_url' => 'http://example.com/course',
    //         'realization_start_date' => '2024-09-01 00:00:00',
    //         'realization_finish_date' => '2024-12-01 00:00:00',
    //     ]);

    //     // Crear relaciones de prueba
    //     $teacher = UsersModel::factory()->create(['email' => 'teacher@example.com']);

    //     $course->teachers()->attach($teacher->uid, ['uid' => generate_uuid()]);

    //     $student = UsersModel::factory()->create([
    //         'uid' => generate_uuid(),
    //         'email' => 'student@example.com'
    //     ]);

    //     CoursesStudentsModel::factory()->create([
    //         'uid'               => generate_uuid(),
    //         'course_uid'        => $course->uid,
    //         'user_uid'          => $student->uid,
    //         'acceptance_status' => 'ACCEPTED',
    //         'status' => 'ENROLLED'
    //     ]);

    //     // Realizar la solicitud GET para obtener el curso
    //     $response = $this->getJson('/api/get_course/' . $course->course_lms_uid, [
    //         'API-KEY' => $apikey->api_key
    //     ]);

    //     // Verificar que la respuesta sea 200 (OK)
    //     $response->assertStatus(200);
    // }
    // /**
    //  * @test  Verifica que se puede obtener un curso correctamente y no pertenece al programa educativo.
    //  */
    // public function testGetCourseSuccessfullyApiGetCourseNotBelongToEP()
    // {
    //     // Crea un usuario y actúa como él
    //     $admin = UsersModel::factory()->create();
    //     $this->actingAs($admin);

    //     // Datos de para genera la key de la api
    //     $apikey = ApiKeysModel::factory()->create()->first();

    //     $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);
    //     // Crea un usuario y actúa como él
    //     $admin = UsersModel::factory()->create();
    //     $this->actingAs($admin);

    //     // Simular la carga de datos que haría el GeneralOptionsMiddleware
    //     $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
    //     View::share('general_options', $general_options);

    //     // Datos de para genera la key de la api
    //     $apikey = ApiKeysModel::factory()->create()->first();

    //     $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

    //     $educationalProg = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->first();

    //     // Crear un curso de prueba con datos relacionados
    //     $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
    //         'course_lms_uid' => generate_uuid(),
    //         'title' => 'Curso de prueba',
    //         'description' => 'Descripción del curso de prueba',
    //         'ects_workload' => 10,
    //         'educational_program_uid' => $educationalProg->uid,
    //         'belongs_to_educational_program' => false,
    //         'lms_url' => 'http://example.com/course',
    //         'realization_start_date' => '2024-09-01 00:00:00',
    //         'realization_finish_date' => '2024-12-01 00:00:00',
    //     ]);

    //     // Crear relaciones de prueba
    //     $teacher = UsersModel::factory()->create(['email' => 'teacher@example.com']);

    //     $course->teachers()->attach($teacher->uid, ['uid' => generate_uuid()]);

    //     $student = UsersModel::factory()->create([
    //         'uid' => generate_uuid(),
    //         'email' => 'student@example.com'
    //     ]);

    //     CoursesStudentsModel::factory()->create([
    //         'uid'               => generate_uuid(),
    //         'course_uid'        => $course->uid,
    //         'user_uid'          => $student->uid,
    //         'acceptance_status' => 'ACCEPTED',
    //         'status' => 'ENROLLED'
    //     ]);

    //     // Realizar la solicitud GET para obtener el curso
    //     $response = $this->getJson('/api/get_course/' . $course->course_lms_uid, [
    //         'API-KEY' => $apikey->api_key
    //     ]);

    //     // Verificar que la respuesta sea 200 (OK)
    //     $response->assertStatus(200);
    // }
}
