<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use App\Models\CoursesModel;
use App\Models\GeneralOptionsModel;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\View;
use App\Models\EducationalProgramsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiGetCourseControllerTest extends TestCase
{
    use RefreshDatabase;


    /**
     * @test  Verifica que se maneja correctamente cuando el curso no es encontrado.
     */
    public function testCourseNotFoundApiGetCourse()
    {
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Realizar la solicitud GET con un `course_lms_uid` que no existe
        $response = $this->getJson('/api/get_course/nonexistent-uid',[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 404 (Not Found)
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Curso no encontrado']);
    }

    /**
     * @test  Verifica que se puede obtener un curso correctamente y pertenece al programa educativo.
     */
    public function testGetCourseSuccessfullyApiGetCourse()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Simular la carga de datos que haría el GeneralOptionsMiddleware
        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        $educationalProg = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->first();

        // Crear un curso de prueba con datos relacionados
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'course_lms_uid' => 'lms-uid-123',
            'title' => 'Curso de prueba',
            'description' => 'Descripción del curso de prueba',
            'ects_workload' => 10,
            'educational_program_uid'=>$educationalProg->uid,
            'belongs_to_educational_program' => true,
            'lms_url' => 'http://example.com/course',
            'realization_start_date' => '2024-09-01 00:00:00',
            'realization_finish_date' => '2024-12-01 00:00:00',
        ]);

        // Crear relaciones de prueba
        $teacher = UsersModel::factory()->create(['email' => 'teacher@example.com']);

        $course->teachers()->attach($teacher->uid, ['uid' => generate_uuid()]);

        $student = UsersModel::factory()->create([
            'uid' => generate_uuid(),
            'email' => 'student@example.com'
        ]);

        CoursesStudentsModel::factory()->create([
            'uid'               => generate_uuid(),
            'course_uid'        => $course->uid,
            'user_uid'          => $student->uid,
            'acceptance_status' => 'ACCEPTED',
            'status' => 'ENROLLED'
        ]);

        // Realizar la solicitud GET para obtener el curso
        $response = $this->getJson('/api/get_course/' . $course->course_lms_uid, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

    }
    /**
     * @test  Verifica que se puede obtener un curso correctamente y no pertenece al programa educativo.
     */
    public function testGetCourseSuccessfullyApiGetCourseNotBelongToEP()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Simular la carga de datos que haría el GeneralOptionsMiddleware
        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        $educationalProg = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->first();

        // Crear un curso de prueba con datos relacionados
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'course_lms_uid' => 'lms-uid-123',
            'title' => 'Curso de prueba',
            'description' => 'Descripción del curso de prueba',
            'ects_workload' => 10,
            'educational_program_uid'=>$educationalProg->uid,
            'belongs_to_educational_program' => false,
            'lms_url' => 'http://example.com/course',
            'realization_start_date' => '2024-09-01 00:00:00',
            'realization_finish_date' => '2024-12-01 00:00:00',
        ]);

        // Crear relaciones de prueba
        $teacher = UsersModel::factory()->create(['email' => 'teacher@example.com']);

        $course->teachers()->attach($teacher->uid, ['uid' => generate_uuid()]);

        $student = UsersModel::factory()->create([
            'uid' => generate_uuid(),
            'email' => 'student@example.com'
        ]);

        CoursesStudentsModel::factory()->create([
            'uid'               => generate_uuid(),
            'course_uid'        => $course->uid,
            'user_uid'          => $student->uid,
            'acceptance_status' => 'ACCEPTED',
            'status' => 'ENROLLED'
        ]);

        // Realizar la solicitud GET para obtener el curso
        $response = $this->getJson('/api/get_course/' . $course->course_lms_uid, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

    }

}
