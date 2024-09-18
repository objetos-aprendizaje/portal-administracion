<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Http\Controllers\LearningObjects\EducationalProgramsController;

class LearningObjectProgramsEducationalSaveTest extends TestCase
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
     * Verifica que un nuevo programa educativo se crea correctamente.
     */
    public function testCreatesANewEducationalProgram()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        // Datos de prueba
        $data = [
            'name' => 'Programa Educativo de Prueba',
            'educational_program_type_uid' => $programType->uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 100,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode(['category1', 'category2']),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];


        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Programa formativo añadido correctamente']);

        // Verificar que el programa educativo se haya guardado en la base de datos
        $this->assertDatabaseHas('educational_programs', ['name' => 'Programa Educativo de Prueba']);
    }

    /**
     * @test
     * Verifica que un programa educativo existente se actualiza correctamente.
     */
    public function testUpdatesAnExistingEducationalProgram()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        // Crear un programa educativo existente
        $existingProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'name' => 'Programa Educativo Original',
        ]);

        // Datos de prueba para la actualización
        $data = [
            'educational_program_uid' => $existingProgram->uid,
            // 'educational_program_type_uid' => $programType->uid,
            'name' => 'Programa Educativo Actualizado',
            'educational_program_type_uid' => $existingProgram->educational_program_type_uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Nuevos Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 150,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode(['category1', 'category2']),
            'documents' => json_encode([]),
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Programa formativo actualizado correctamente']);

        // Verificar que los cambios se hayan guardado en la base de datos
        $this->assertDatabaseHas('educational_programs', ['uid' => $existingProgram->uid, 'name' => 'Programa Educativo Actualizado']);
    }

    /**
     * @test
     * Verifica que la validación de un programa educativo falle con datos incorrectos.
     */
    public function testFailsToCreateAnEducationalProgramWithInvalidData()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
        ]);

        // Datos de prueba con errores (por ejemplo, sin nombre)
        $data = [
            'educational_program_type_uid' => generate_uuid(),
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-08-30',
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 400 (Bad Request) y contenga el mensaje de error esperado
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Algunos campos son incorrectos']);

        // Verificar que el programa educativo no se haya guardado en la base de datos
        $this->assertDatabaseMissing('educational_programs', ['name' => null]);
    }

    /** @test */
    public function testReturnsPendingApprovalStatusOrNoStatus()
    {
         // Simulamos los estados del modelo como objetos stdClass
         $statuses = collect([
            (object)['code' => 'INTRODUCTION'],
            (object)['code' => 'PENDING_APPROVAL'],
            (object)['code' => 'UNDER_CORRECTION_APPROVAL'],
            (object)['code' => 'UNDER_CORRECTION_PUBLICATION'],
            (object)['code' => 'PENDING_PUBLICATION'],
        ]);

        // Mockear el modelo para devolver los estados simulados
        $mock = Mockery::mock(EducationalProgramStatusesModel::class);
        $mock->shouldReceive('whereIn')
            ->with('code', [
                'INTRODUCTION',
                'PENDING_APPROVAL',
                'UNDER_CORRECTION_APPROVAL',
                'UNDER_CORRECTION_PUBLICATION',
                'PENDING_PUBLICATION'
            ])
            ->andReturnSelf();
        $mock->shouldReceive('get')
            ->andReturn($statuses); // Asegúrate de que esto devuelva una colección de stdClass

        // Reemplazar el modelo mockeado
        $this->app->instance(EducationalProgramStatusesModel::class, $mock);

        // Crear una instancia del controlador
        $controller = new EducationalProgramsController();

        // Usar reflexión para acceder al método privado
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('statusEducationalProgramUserTeacher');
        $method->setAccessible(true);

        // Probar el caso cuando el estado actual es INTRODUCTION
        $result = $method->invokeArgs($controller, ['submit', 'INTRODUCTION']);
        $this->assertEquals('PENDING_APPROVAL', $result->code);
        // Probar el caso cuando no hay estado actual
        $result = $method->invokeArgs($controller, ['submit', null]);
        $this->assertEquals('PENDING_APPROVAL', $result->code);

        // Probar el caso cuando el estado actual es UNDER_CORRECTION_APPROVAL
        $result = $method->invokeArgs($controller, ['submit', 'UNDER_CORRECTION_APPROVAL']);
        $this->assertEquals('PENDING_APPROVAL', $result->code);
        // Probar el caso cuando el estado actual es UNDER_CORRECTION_PUBLICATION
        $result = $method->invokeArgs($controller, ['submit', 'UNDER_CORRECTION_PUBLICATION']);
        $this->assertEquals('PENDING_PUBLICATION', $result->code);
        // Probar el caso cuando el action es "draft" y el estado actual es INTRODUCTION
        $result = $method->invokeArgs($controller, ['draft', 'INTRODUCTION']);
        $this->assertEquals('INTRODUCTION', $result->code);

        // Probar el caso cuando el action es "draft" y no hay estado actual
        $result = $method->invokeArgs($controller, ['draft', null]);
        $this->assertEquals('INTRODUCTION', $result->code);

        // Probar el caso cuando el action no es "submit" ni "draft"
        $result = $method->invokeArgs($controller, ['other_action', 'INTRODUCTION']);
        $this->assertNull($result);
    }





}
