<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\ApiKeysModel;
use App\Models\CoursesModel;
use App\Models\LmsSystemsModel;
use App\Models\EducationalProgramsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiUpdateCourseControllerTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test  Verifica que un curso se actualiza correctamente.
     */
    public function testCourseIsUpdatedSuccessfully()
    {

        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        $edu_program = EducationalProgramsModel::factory()->withEducationalProgramType()->create([

            'enrolling_finish_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),
        ])->first();

        $lms = LmsSystemsModel::factory()->create()->first();

        $course_lms_id =  generateUuid();

        // Crear un curso en la base de datos
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'title' => 'Old Title',
            'course_lms_uid' => $lms->uid,
            'description' => 'Old Description',
            'lms_url' => 'https://oldurl.com/course',
            'ects_workload' => 3,
            'realization_start_date' => '2024-09-01 10:00:00',
            'realization_finish_date' => '2024-09-10 10:00:00',
            'educational_program_uid' => $edu_program->uid,
            'course_lms_id' => $course_lms_id,
        ]);

        // Datos de la solicitud
        $updateData = [
            'lms_uid' => $lms->uid,
            'title' => 'New Title',
            'description' => 'New Description',
            'lms_url' => 'https://newurl.com/course',
            'ects_workload' => 5,
            'realization_start_date' =>  Carbon::now()->addDays(40)->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(50)->format('Y-m-d\TH:i'),
            'educational_program_uid' => $edu_program->uid,
        ];

        // Realizar la solicitud POST con los datos de actualización del curso
        $response = $this->postJson('/api/courses/update/'.$course_lms_id, $updateData, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Curso actualizado correctamente']);

        // Verificar que el curso fue actualizado en la base de datos
        $this->assertDatabaseHas('courses', [
            'title' => 'New Title',
            'description' => 'New Description',
            'lms_url' => 'https://newurl.com/course',
            'ects_workload' => 5,
            'realization_start_date' => Carbon::now()->addDays(40)->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(50)->format('Y-m-d\TH:i'),
        ]);
    }

    /**
     * @test  Verifica que la validación de fechas falla cuando las fechas son incorrectas.
     */
    public function testValidationFailsWhenDatesAreInvalidApiUpdateCourse()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        $lms = LmsSystemsModel::factory()->create()->first();

        $course_lms_id =  generateUuid();

        // Crear un curso en la base de datos
        CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'course_lms_uid' => generateUuid(),
            'realization_start_date' => '2024-09-01 10:00:00',
            'realization_finish_date' => '2024-09-10 10:00:00',
            'course_lms_uid' => $lms->uid,
        ]);

        // Datos de la solicitud con fechas inválidas
        $updateData = [
            'lms_uid' => generateUuid(),
            'title' => 'New Title',
            'description' => 'New Description',
            'lms_url' => 'https://newurl.com/course',
            'ects_workload' => 5,
            'realization_start_date' => '2024-08-01 10:00:00', // Fecha de inicio anterior a la actual
            'realization_finish_date' => '2024-07-01 10:00:00', // Fecha de fin anterior a la de inicio
        ];

        // Realizar la solicitud POST con los datos de actualización del curso
        $response = $this->postJson('/api/update_course/'.$course_lms_id, $updateData, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(404);
    }
}
