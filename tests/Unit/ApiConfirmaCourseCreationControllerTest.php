<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use App\Models\CoursesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
class ApiConfirmaCourseCreationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test  Verifica que un curso se confirma correctamente.
     */
    public function testCourseIsConfirmedSuccessfully()
    {

        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Crear un curso en la base de datos
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'uid' => generateUuid(),
            'course_lms_uid' => null,
            'lms_url' => null,
        ]);

        $lmsUid = Str::uuid();
        // Datos de la solicitud
        $courseConfirm = [
            'lms_uid' => $lmsUid,
            'poa_uid' => $course->uid,
            'lms_url' => 'https://example.com/course',
            'course_lms_id' => generateUuid(),
        ];

        // Realizar la solicitud POST con los datos del curso
        $response = $this->postJson('/api/courses/confirm_course_creation', $courseConfirm, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Curso confirmado correctamente']);

        // Verificar que el curso fue actualizado en la base de datos
        $this->assertDatabaseHas('courses', [
            'uid' => $course->uid,
            'course_lms_uid' => null,
            'lms_url' => 'https://example.com/course',
        ]);
    }

    /**
     * @test  Verifica que la validación falla cuando el poa_uid no existe.
     */
     public function testValidationFailsWhenPoaUidDoesNotExist()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Datos de la solicitud con un poa_uid que no existe
        $courseConfirm = [
            'lms_uid' => Str::uuid(),
            'poa_uid' => Str::uuid(),
            'lms_url' => 'https://example.com/course',
        ];

        // Realizar la solicitud POST con los datos del curso
        $response = $this->postJson('/api/courses/confirm_course_creation', $courseConfirm,[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene el mensaje de error esperado para poa_uid
        $response->assertJsonValidationErrors(['poa_uid']);
    }
}
