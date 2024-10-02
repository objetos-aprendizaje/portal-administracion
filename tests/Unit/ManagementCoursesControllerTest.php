<?php

namespace Tests\Unit;


use Mockery;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use App\Models\BlocksModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use PhpParser\Node\Stmt\Block;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\CourseTypesModel;
use App\Models\CourseStatusesModel;
use App\Services\EmbeddingsService;
use App\Models\CoursesTeachersModel;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Management\ManagementCoursesController;


class ManagementCoursesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /** @Group Duplicatecourse */
    /** @test Duplicar curso*/
    public function testDuplicateACourse()
    {

        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Autenticar al usuario
        $this->actingAs($user);

        // Crear un curso de prueba
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'belongs_to_educational_program' => false,
        ]);

        // Crear un estado "INTRODUCTION" para los cursos
       $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

        // Hacer la solicitud POST a la ruta de duplicación
        $response = $this->postJson("/learning_objects/courses/duplicate_course/{$course->uid}", [
            'course_uid' => $course->uid,
        ]);

        // Verificar que la respuesta sea 200
        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Curso duplicado correctamente',
        ]);

        // Verificar que el curso haya sido duplicado en la base de datos
         $this->assertDatabaseHas('courses', [
            'title' => $course->title . " (copia)",
            'course_status_uid' => $status->uid,
        ]);


        // Verificar que el nuevo curso tenga un UID diferente
        $newCourse = CoursesModel::where('title', $course->title . " (copia)")->first();
        $this->assertNotEquals($course->uid, $newCourse->uid);
    }

    public function testDuplicateACourseFail()
{
    // Crear un usuario de prueba
    $user = UsersModel::factory()->create();

    // Autenticar al usuario
    $this->actingAs($user);

    // Crear un curso de prueba que pertenezca a un programa formativo
    $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
        'belongs_to_educational_program' => true,
    ]);

    // Crear un estado "INTRODUCTION" para los cursos
    $status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();

    // Hacer la solicitud POST a la ruta de duplicación
    $response = $this->postJson("/learning_objects/courses/duplicate_course/{$course->uid}", [
        'course_uid' => $course->uid,
    ]);

    // Verificar que la respuesta tenga un código de estado 422 (Unprocessable Entity)
    $response->assertStatus(422);

    // Verificar que la respuesta contenga el mensaje de error esperado
    $response->assertJson([
        'message' => 'No puedes duplicar un curso que pertenezca a un programa formativo',
    ]);

    // Verificar que no se haya duplicado el curso en la base de datos
    $this->assertDatabaseMissing('courses', [
        'title' => $course->title . " (copia)",
    ]);
}


    public function testStatusCourseEdition()
    {
            // Crear un mock del modelo CourseStatusesModel
        $mockStatus = Mockery::mock(CourseStatusesModel::class);
        $mockStatus->shouldReceive('whereIn')
            ->with('code', ['INTRODUCTION', 'ACCEPTED_PUBLICATION', 'PENDING_APPROVAL'])
            ->andReturn(collect([
                (object)['code' => 'INTRODUCTION'],
                (object)['code' => 'ACCEPTED_PUBLICATION'],
                (object)['code' => 'PENDING_APPROVAL'],
            ]));

        // Reemplazar el modelo en el contenedor de Laravel
        $this->app->instance(CourseStatusesModel::class, $mockStatus);

        // Crear un mock de la configuración general
        app()->instance('general_options', ['necessary_approval_editions' => true]);

        // Create a mock for EmbeddingsService
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService);

        // Use Reflection to access the private method applyFilters
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('statusCourseEdition');
        $method->setAccessible(true);



        // Caso 1: Acción "draft" sin estado actual
        $course_bd = (object)['status' => null];
        $result = $method->invokeArgs($controller, ['draft', $course_bd]);
        $this->assertEquals('INTRODUCTION', $result->code);

        // Caso 2: Acción "submit" con aprobación necesaria
        $course_bd = (object)['status' => (object)['code' => 'INTRODUCTION']];
        $result = $method->invokeArgs($controller, ['submit', $course_bd]);
        $this->assertEquals('PENDING_APPROVAL', $result->code);

        // Caso 3: Acción "submit" sin aprobación necesaria
        app()->instance('general_options', ['necessary_approval_editions' => false]);
        $result = $method->invokeArgs($controller, ['submit', $course_bd]);
        $this->assertEquals('ACCEPTED_PUBLICATION', $result->code);
    }


    // Cierra Mockery después de las pruebas
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testApplyFiltersCourses()
    {

        // Crear tipos de programas educativos
        $educational_programType1 = EducationalProgramTypesModel::factory()->create()->latest()->first();

        $center1 = CentersModel::factory()->create([
            'uid'  => generate_uuid(),
            'name' => 'Centro 1'
        ])->latest()->first();

        $coursestatuses1 = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $course_type1 = CourseTypesModel::factory()->create()->first();

        $teacher1 = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
        $teacher1->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Crear datos de prueba
        $course1 = CoursesModel::create([
            'uid' => generate_uuid(),
            'center_uid' => $center1->uid,
            'title' => 'Curso 1',
            'description' => 'Description',
            'course_status_uid' => $coursestatuses1->uid,
            'course_type_uid' => $course_type1->uid,
            'educational_program_type_uid' => $educational_programType1->uid,
            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(29)->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now()->addDays(61)->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
            'ects_workload' => 10,
            'identifier' => 'CUR-8154744',
            'cost' => 100,
            'min_required_students' => 5,
            'creator_user_uid' => $teacher1->uid,
            'payment_mode' => 'SINGLE_PAYMENT'
        ])->first();

        $course1->update(['center_uid' => $center1->uid]);

        $course1->teachers()->attach($teacher1, ['uid' => generate_uuid()]);

        $coordinator = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
        $coordinator->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Crear Coordinador de curso
        $existingCourseCoordinator = CoursesTeachersModel::where('course_uid', $course1->uid)
            ->where('user_uid', $coordinator->uid)
            ->first();

        if (!$existingCourseCoordinator) {
            // Create a new course coordinator entry if it doesn't exist
            $coursecoordinator = CoursesTeachersModel::factory()->create([
                'uid' => generate_uuid(),
                'course_uid' => $course1->uid,
                'user_uid' => $coordinator->uid,
                'type' => 'COORDINATOR'
            ])->first();
            $course1->teachers()->attach($coursecoordinator->user_uid,['uid' => generate_uuid()]);
        }

         // Crear No-Coordinador de curso
         $existingCourseNoCoordinator = CoursesTeachersModel::where('course_uid', $course1->uid)
         ->where('type', 'NO_COORDINATOR')
         ->first();

        if (!$existingCourseNoCoordinator) {
            // Create a new course coordinator entry if it doesn't exist
            $coursenocoordinator = CoursesTeachersModel::factory()->create([
                'uid' => generate_uuid(),
                'course_uid' => $course1->uid,
                'user_uid' => $coordinator->uid,
                'type' => 'COORDINATOR'
            ])->first();
            $course1->teachers()->attach($coursenocoordinator->user_uid,['uid' => generate_uuid()]);

        }


        $category1 = CategoriesModel::factory()->create()->first();
        $course1->categories()->attach($category1->uid,['uid' => generate_uuid()]);


        $course1->update(['course_status_uid' => $coursestatuses1->uid]);


        $call1 = CallsModel::factory()->create()->latest()->first();
        $course1->update(['call_uid' => $call1->uid]);


        $educational_program1 = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->latest()->first();
        $course1->update(['educational_program_uid ' => $educational_program1->uid]);


        $course1->update(['course_type_uid' => $course_type1->uid]);

            // Crear un bloque y asociarlo con el curso
        $block = BlocksModel::factory()->create(['uid' => generate_uuid(), 'course_uid' => $course1->uid]);

        // Crear una competencia
        $competence = CompetencesModel::factory()->create()->latest()->first();

        // Asociar la competencia con el bloque
        $block->competences()->attach($competence->uid, ['uid' => generate_uuid()]);


        // Create a mock for EmbeddingsService
        $mockEmbeddingsService = $this->createMock(EmbeddingsService::class);

        // Instantiate ManagementCoursesController with the mocked service
        $controller = new ManagementCoursesController($mockEmbeddingsService);

        // Use Reflection to access the private method applyFilters
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('applyFilters');
        $method->setAccessible(true);

        // Prepare any parameters needed for applyFilters
        $parameters = ['exampleData']; // Adjust this based on what applyFilters expects


        $inscrip_date1 = Carbon::now()->format('Y-m-d\TH:i');
        $inscrip_date2 = Carbon::now()->addDays(30)->format('Y-m-d\TH:i');

        //Caso 2 Filtrar por fecha Inscription Rango
        $filtersDate = [['database_field' => 'inscription_date', 'value' => [$inscrip_date1, $inscrip_date2]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersDate, &$query]);
        $filteredCoursesByDate = $query->get();
        // Verificar que el curso devuelto está dentro del rango de fechas
        $this->assertEquals($course1->uid, $filteredCoursesByDate->first()->uid);

        // Caso 3: Filtrar por fecha de inscripción única
        $filters = [['database_field' => 'inscription_date', 'value' => [$inscrip_date1]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filters, &$query]);
        $this->assertGreaterThan(0, CoursesModel::count());

        // Caso 4: Filtrar por fecha de realización Rango
        $date_realization1 = Carbon::now()->addDays(61)->format('Y-m-d\TH:i');
        $date_realization2 = Carbon::now()->addDays(90)->format('Y-m-d\TH:i');
        $filtersRealization = [['database_field' => 'realization_date', 'value' => [$date_realization1,$date_realization2]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersRealization, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 5: Filtrar por fecha de realización (fecha única)
        $filters = [['database_field' => 'realization_date', 'value' => [$date_realization1]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filters, &$query]);
        $this->assertGreaterThan(0, CoursesModel::count());

        // Caso 6: Filtrar por Tipo de teachers Coordinador
        $filtersCreator = [['database_field' => 'coordinators_teachers', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersCreator, &$query]);
        $this->assertEmpty($query->get());

        // Caso 7: Filtrar por Tipo de teachers No-Coordinador
        $filtersCreator = [['database_field' => 'no_coordinators_teachers', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersCreator, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 8: Filtrar por usuario creador
        $filtersCreator = [['database_field' => 'creator_user_uid', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersCreator, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 9: Filtrar por categorías
        $filterscategory = [['database_field' => 'categories', 'value' => [$category1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filterscategory, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 10: Filtrar por estados del curso
        $filtersstatus = [['database_field' => 'course_statuses', 'value' => [$course1->course_status_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersstatus, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 11: Filtrar por convocatorias
        $filterscall = [['database_field' => 'calls', 'value' => [$call1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filterscall, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 12: Filtrar por programas educativos
        $filtersep = [['database_field' => 'educational_programs', 'value' => [$educational_program1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersep, &$query]);
        $this->assertEmpty($query->get());



         // Caso 13: Filtrar por tipos de curso
         $filterstype = [['database_field' => 'course_types', 'value' => [$course_type1->uid]]];
         $query = CoursesModel::query();
         $method->invokeArgs($controller, [$filterstype, &$query]);
         $this->assertNotEmpty($query->get());


         // Caso 14: Filtrar por carga de ECTS mínima
        $filtersminwork = [['database_field' => 'min_ects_workload', 'value' => 10]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersminwork, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 15: Filtrar por carga de ECTS mínima
        $filtersmaxwork = [['database_field' => 'max_ects_workload', 'value' => 10]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmaxwork, &$query]);
        $this->assertNotEmpty($query->get());

         // Caso 16: Filtrar por coste mínimo
         $filtersmincost = [['database_field' => 'min_cost', 'value' => 100]];
         $query = CoursesModel::query();
         $method->invokeArgs($controller, [$filtersmincost, &$query]);
         $this->assertNotEmpty($query->get());

          // Caso 17: Filtrar por coste máximo
        $filtersmaxcost = [['database_field' => 'max_cost', 'value' => 100]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmaxcost, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 18: Filtrar por estudiantes requeridos mínimos
        $filtersminstudent = [['database_field' => 'min_required_students', 'value' => 5]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersminstudent, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 19: Filtrar por estudiantes requeridos máximos
        $filtersmaxstudent = [['database_field' => 'max_required_students', 'value' => 7]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersmaxstudent, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 20: Filtrar por competencias
        $filterscompetence = [['database_field' => 'learning_results', 'value' => [$competence->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [ $filterscompetence, &$query]);

        // Caso 21: Cualquier otro campo
        $filtersep = [['database_field' => 'evaluation_criteria', 'value' => 'EV']];
        $query = CoursesModel::query();
        $method->invokeArgs($controller, [$filtersep, &$query]);
        $this->assertEmpty($query->get());


    }


}

