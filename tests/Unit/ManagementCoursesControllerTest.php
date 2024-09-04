<?php

namespace Tests\Unit;


use App\Models\BlocksModel;
use App\Models\CompetencesModel;
use Mockery;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use PhpParser\Node\Stmt\Block;
use App\Models\CategoriesModel;
use App\Models\CourseTypesModel;
use App\Models\CourseStatusesModel;
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
        $course = CoursesModel::factory()->create();

        // Crear un estado "INTRODUCTION" para los cursos
        // $status = CourseStatusesModel::factory()->create(['uid' => generate_uuid(), 'code' => 'INTRODUCTION']);
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

        // Crear una instancia de la clase que contiene el método privado
        $class = new ManagementCoursesController(); // Reemplaza con el nombre de tu clase

        // Probar el método privado con reflexión
        $method = (new \ReflectionClass($class))->getMethod('statusCourseEdition');
        $method->setAccessible(true);

        // Caso 1: Acción "draft" sin estado actual
        $course_bd = (object)['status' => null];
        $result = $method->invokeArgs($class, ['draft', $course_bd]);
        $this->assertEquals('INTRODUCTION', $result->code);

        // Caso 2: Acción "submit" con aprobación necesaria
        $course_bd = (object)['status' => (object)['code' => 'INTRODUCTION']];
        $result = $method->invokeArgs($class, ['submit', $course_bd]);
        $this->assertEquals('PENDING_APPROVAL', $result->code);

        // Caso 3: Acción "submit" sin aprobación necesaria
        app()->instance('general_options', ['necessary_approval_editions' => false]);
        $result = $method->invokeArgs($class, ['submit', $course_bd]);
        $this->assertEquals('ACCEPTED_PUBLICATION', $result->code);
    }


    // Cierra Mockery después de las pruebas
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testApplyFilters()
    {



        // Crear tipos de programas educativos
        $educational_programType1 = EducationalProgramTypesModel::factory()->create();

        $center1 = CentersModel::factory()->create([
            'uid'=>generate_uuid(),
            'name' => 'Centro 1'
        ])->latest()->first();


        $coursestatuses1 = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $course_type1 = CourseTypesModel::factory()->create()->first();

        $teacher1 = UsersModel::factory()->create()->latest()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
        $teacher1->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Crear datos de prueba
        $course1 = CoursesModel::create([
            'uid' => generate_uuid(),
            'center_uid' => $center1->uid,
            'title' => 'Curso 1',
            'course_status_uid' => $coursestatuses1->uid,
            'course_type_uid' => $course_type1->uid,
            'educational_program_type_uid' => $educational_programType1->uid,
            'inscription_start_date' => '2023-01-01',
            'inscription_finish_date' => '2023-12-31',
            'realization_start_date' => '2023-06-15',
            'realization_finish_date' => '2023-07-01',
            'ects_workload' => 10,
            'identifier' => 'CUR-8154744',
            'cost' => 100,
            'min_required_students' => 5,
            'creator_user_uid' => $teacher1->uid
        ])->first();


        $course1->teachers()->attach($teacher1, ['uid' => generate_uuid()]);

        $category1 = CategoriesModel::factory()->create()->first();
        $course1->categories()->attach($category1->uid,['uid' => generate_uuid()]);


        $course1->update(['course_status_uid' => $coursestatuses1->uid]);


        $call1 = CallsModel::factory()->create()->latest()->first();
        $course1->update(['call_uid' => $call1->uid]);


        $educational_program1 = EducationalProgramsModel::factory()->create()->first();
        $course1->update(['educational_program_uid' => $educational_program1->uid]);


        $course1->update(['course_type_uid' => $course_type1->uid]);

            // Crear un bloque y asociarlo con el curso
        $block = BlocksModel::factory()->create(['uid' => generate_uuid(), 'course_uid' => $course1->uid]);

        // Crear una competencia
        $competence = CompetencesModel::factory()->create()->latest()->first();

        // Asociar la competencia con el bloque
        $block->competences()->attach($competence->uid, ['uid' => generate_uuid()]);


        // Probar el método privado
        $class = new ManagementCoursesController();
        $method = (new \ReflectionClass($class))->getMethod('applyFilters');
        $method->setAccessible(true);



        //Caso 2 Filtrar por fecha Inscription Rango
        $filtersDate = [['database_field' => 'inscription_date', 'value' => ['2023-01-01', '2023-12-31']]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersDate, &$query]);
        $filteredCoursesByDate = $query->get();
        // Verificar que el curso devuelto está dentro del rango de fechas
        $this->assertEquals($course1->uid, $filteredCoursesByDate->first()->uid);

        // Caso 3: Filtrar por fecha de inscripción única
        $filters = [['database_field' => 'inscription_date', 'value' => ['2024-06-01']]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filters, &$query]);
        $this->assertEquals(0, $query->count());

        // Caso 4: Filtrar por fecha de realización Rango
        $filtersRealization = [['database_field' => 'realization_date', 'value' => ['2023-06-01','2023-08-31']]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersRealization, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 5: Filtrar por fecha de realización (fecha única)
        $filters = [['database_field' => 'realization_date', 'value' => ['2024-06-01']]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filters, &$query]);
        $this->assertEquals(0, $query->count());

        // Caso 6: Filtrar por usuario creador
        $filtersCreator = [['database_field' => 'creator_user_uid', 'value' => [$course1->creator_user_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersCreator, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 7: Filtrar por categorías
        $filterscategory = [['database_field' => 'categories', 'value' => [$category1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filterscategory, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 8: Filtrar por estados del curso
        $filtersstatus = [['database_field' => 'course_status_uid', 'value' => [$course1->course_status_uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersstatus, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 9: Filtrar por convocatorias
        $filterscall = [['database_field' => 'calls', 'value' => [$call1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filterscall, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 10: Filtrar por programas educativos
        $filtersep = [['database_field' => 'educational_program_uid', 'value' => [$educational_program1->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersep, &$query]);
        $this->assertNotEmpty($query->get());


         // Caso 11: Filtrar por tipos de curso
         $filterstype = [['database_field' => 'course_type_uid', 'value' => [$course_type1->uid]]];
         $query = CoursesModel::query();
         $method->invokeArgs($class, [$filterstype, &$query]);
         $this->assertNotEmpty($query->get());


         // Caso 12: Filtrar por carga de ECTS mínima
        $filtersminwork = [['database_field' => 'min_ects_workload', 'value' => 10]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersminwork, &$query]);
        $this->assertNotEmpty($query->get());

         // Caso 14: Filtrar por coste mínimo
         $filtersmincost = [['database_field' => 'min_cost', 'value' => 100]];
         $query = CoursesModel::query();
         $method->invokeArgs($class, [$filtersmincost, &$query]);
         $this->assertNotEmpty($query->get());

          // Caso 15: Filtrar por coste máximo
        $filtersmaxcost = [['database_field' => 'max_cost', 'value' => 100]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersmaxcost, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 16: Filtrar por estudiantes requeridos mínimos
        $filtersminstudent = [['database_field' => 'min_required_students', 'value' => 5]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersminstudent, &$query]);
        $this->assertNotEmpty($query->get());

        // Caso 17: Filtrar por estudiantes requeridos máximos
        $filtersmaxstudent = [['database_field' => 'max_required_students', 'value' => 7]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [$filtersmaxstudent, &$query]);
        $this->assertNotEmpty($query->get());


        // Caso 18: Filtrar por competencias
        $filterscompetence = [['database_field' => 'competences', 'value' => [$competence->uid]]];
        $query = CoursesModel::query();
        $method->invokeArgs($class, [ $filterscompetence, &$query]);
        $this->assertNotEmpty($query->get()); // Asegúrate de que el curso se cuenta
      }




}

