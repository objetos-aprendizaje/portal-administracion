<?php

namespace Tests\Unit;


use Tests\TestCase;
use ReflectionClass;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Services\EmbeddingsService;
use Illuminate\Support\Facades\Auth;
use App\Services\CertidigitalService;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramsModel;
use App\Exceptions\OperationFailedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\LearningObjects\CoursesController;
use App\Http\Controllers\Management\ManagementCoursesController;

class LearningObjectEducationalResourceTest extends TestCase
{

    use RefreshDatabase;
    protected $courseService;

    public function setUp(): void
    {


        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }


    /**
     * @test   Verifica que se devuelve un error cuando status incorrecto.
     */
    public function testStatusCourseFailsWhenStatusIsNotAllowed()
    {
        // Simula un usuario sin rol de MANAGEMENT
        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Simula un curso con un estado que no está permitido
        $course_bd = new CoursesModel();
        $course_bd->status = (object)['code' => 'SOME_OTHER_STATUS'];
        $course_bd->belongs_to_educational_program = false;

        // Usa la reflexión para acceder al método privado
        $reflection = new ReflectionClass(CoursesController::class);
        $method = $reflection->getMethod('checkStatusCourse');
        $method->setAccessible(true);

        // Asegura que se lanza la excepción
        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('No puedes editar un curso que no esté en estado de introducción o subsanación');

        // Crear mocks del certificado
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new CoursesController($mockEmbeddingsService, $certidigitalServiceMock);
        $method->invokeArgs($controller, [$course_bd]);
    }

    public function testCheckStatusFailsEducationalProgramStatusNotAllowed()
    {
        // Simula un usuario sin rol de MANAGEMENT
        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Crea un curso con el estado 'ADDED_EDUCATIONAL_PROGRAM'
        $course_bd = new CoursesModel();
        $course_bd->status = (object)['code' => 'ADDED_EDUCATIONAL_PROGRAM'];
        $course_bd->belongs_to_educational_program = true;

        // Crea un programa educativo con un estado no permitido
        $educationalProgram = new EducationalProgramsModel();
        $educationalProgram->status = (object)['code' => 'SOME_OTHER_STATUS'];

        $course_bd->educational_program = $educationalProgram;

        // Usa la reflexión para acceder al método privado
        $reflection = new ReflectionClass(CoursesController::class);
        $method = $reflection->getMethod('checkStatusCourse');
        $method->setAccessible(true);

        // Asegura que se lanza la excepción
        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('No puedes editar un curso cuyo programa formativo no esté en estado de introducción o subsanación');

        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Crear mocks del certificado
        $certidigitalServiceMock = $this->createMock(CertidigitalService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new CoursesController($mockEmbeddingsService, $certidigitalServiceMock);
        $method->invokeArgs($controller, [$course_bd]);
    }



}
