<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use App\Models\CoursesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $course = CoursesModel::factory()->create([
            'uid' => generate_uuid(),
            'course_lms_uid' => null,
            'lms_url' => null,
        ]);

        // Datos de la solicitud
        $courseConfirm = [
            'lms_uid' => 'lms-1234',
            'poa_uid' => $course->uid,
            'lms_url' => 'https://example.com/course',
        ];

        // Realizar la solicitud POST con los datos del curso
        $response = $this->postJson('/api/confirm_course_creation', $courseConfirm, [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Curso confirmado correctamente']);

        // Verificar que el curso fue actualizado en la base de datos
        $this->assertDatabaseHas('courses', [
            'uid' => $course->uid,
            'course_lms_uid' => 'lms-1234',
            'lms_url' => 'https://example.com/course',
        ]);
    }

    /**
     * @test  Verifica que la validación falla cuando los datos son incorrectos.
     */
    public function testValidationFailsWithInvalidData()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Crear un curso en la base de datos
        $course = CoursesModel::factory()->create();

        // Datos de la solicitud con campos faltantes o incorrectos
        $courseConfirm = [
            'lms_uid' => '', // Campo lms_uid vacío
            'poa_uid' => $course->uid,
            'lms_url' => 'invalid-url', // URL inválida
        ];

        // Realizar la solicitud POST con los datos del curso
        $response = $this->postJson('/api/confirm_course_creation', $courseConfirm,[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene los mensajes de error esperados
        $response->assertJsonValidationErrors(['lms_uid', 'lms_url']);
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
            'lms_uid' => 'lms-1234',
            'poa_uid' => 'non-existing-uid',
            'lms_url' => 'https://example.com/course',
        ];

        // Realizar la solicitud POST con los datos del curso
        $response = $this->postJson('/api/confirm_course_creation', $courseConfirm,[
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 400 (Bad Request)
        $response->assertStatus(400);

        // Verificar que la respuesta contiene el mensaje de error esperado para poa_uid
        $response->assertJsonValidationErrors(['poa_uid']);
    }
}
