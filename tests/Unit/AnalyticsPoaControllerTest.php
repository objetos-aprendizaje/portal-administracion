<?php

namespace Tests\Unit;

use App\Models\CoursesAccesesModel;
use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourcesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsPoaControllerTest extends TestCase
{
    use RefreshDatabase;

/**
 * @testdox Inicialización de inicio de sesión
 */
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

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
    }


/**
 * @test Get POA Index
 */

     public function testIndexReturnsViewWithData()
    {
        // Realizar la solicitud a la ruta correspondiente al método index
        $response = $this->get('/analytics/poa');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertViewIs('analytics.poa.index') // Verifica que se carga la vista correcta
                 ->assertViewHas('page_name', 'Analíticas de objetos de aprendizaje y recursos') // Verifica el contenido de page_name
                 ->assertViewHas('page_title', 'Analíticas de objetos de aprendizaje y recursos') // Verifica el contenido de page_title
                 ->assertViewHas('resources', [
                     "resources/js/analytics_module/analytics_poa.js",
                     "resources/js/analytics_module/d3.js"
                 ]) // Verifica que los recursos sean los esperados
                 ->assertViewHas('tabulator', true) // Verifica el valor de tabulator
                 ->assertViewHas('submenuselected', 'analytics-poa'); // Verifica el valor de submenuselected
    }
    /** @test Get POA Curso*/
    public function testGetPoaWithDefaultPagination()
    {
        // Arrange: Create mock data
        CoursesModel::factory()->withCourseType()->withCourseStatus()->count(5)->create(); // Create 5 courses

        // Act: Call the endpoint without any parameters
        $response = $this->getJson('/analytics/users/get_poa_get');

        // Assert: Check that the response is successful and contains paginated data
        $response->assertStatus(200);
        $data = $response->json();

        // Assert that we have a paginated response
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']); // Default size is 1
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('per_page', $data);
    }



    /** @test Get POA Curso Sort Asc*/
    public function testGetPoaWithSortAsc()
    {

        $student1 = UsersModel::factory()->create()->first();
        $student2 = UsersModel::factory()->create()->first();

        // Arrange: Create mock data
        $course = CoursesModel::factory()->withCourseType()->withCourseStatus()->count(3)->create();

           // Simular accesos a los cursos
           CoursesAccesesModel::factory()->create(['course_uid' => $course[0]->uid, 'user_uid' => $student1->uid, 'access_date' => now()->subDays(1)]);
           CoursesAccesesModel::factory()->create(['course_uid' => $course[1]->uid, 'user_uid' => $student1->uid, 'access_date' => now()->subDays(2)]);
           CoursesAccesesModel::factory()->create(['course_uid' => $course[2]->uid, 'user_uid' => $student2->uid, 'access_date' => now()->subDays(3)]);


           $response = $this->getJson('/analytics/users/get_poa_get', [
            'size' => 3,
            'sort' => [
                ['field' => 'title', 'dir' => 'asc'],
            ]
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Assert that we have a paginated response
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('per_page', $data);
    }

    /**
     * @test POA Poa Accesses
     *
     */
    public function testGetPoaAccessesReturnsCorrectData()
    {

        $student1 = UsersModel::factory()->create()->first();
        $student2 = UsersModel::factory()->create()->first();


         // Arrange: Create mock data for courses, accesses, and students
         $course = CoursesModel::factory()->withCourseType()->withCourseStatus()->create([
            'uid' => generate_uuid(),
            'realization_start_date' => now(),
             'lms_url' => 'http://example.com/course',
         ]);

         $course->accesses()->createMany([
             ['uid' => generate_uuid(),'user_uid' => $student1->uid, 'course_uid' => $course->uid,'access_date' => now()->subDays(31)],
             ['uid' => generate_uuid(),'user_uid' => $student2->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(10)],
         ]);
        // Realizar la solicitud a la ruta
        $response = $this->getJson(route('analytics-poa-accesses', [
            'size' => 1,
            'search' => null,
            'sort' => [['field' => 'first_access', 'dir' => 'asc']]
        ]));

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'title',
                             'first_access',
                             'last_access',
                         ]
                     ],
                     'links',
                 ]);

    }
    /**
     * @test POA Order desc
     *
     */
    public function testGetPoaAccessesSortDescData()
    {

        $student1 = UsersModel::factory()->create()->first();
        $student2 = UsersModel::factory()->create()->first();


         // Arrange: Create mock data for courses, accesses, and students
         $course = CoursesModel::factory()->withCourseType()->withCourseStatus()->create([
            'uid' => generate_uuid(),
            'realization_start_date' => now(),
             'lms_url' => 'http://example.com/course',
         ]);

         $course->accesses()->createMany([
             ['uid' => generate_uuid(),'user_uid' => $student1->uid, 'course_uid' => $course->uid,'access_date' => now()->subDays(31)],
             ['uid' => generate_uuid(),'user_uid' => $student2->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(10)],
         ]);
        // Realizar la solicitud a la ruta
        $response = $this->getJson(route('analytics-poa-accesses', [
            'size' => 1,
            'search' => null,
        ]));

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'title',
                             'first_access',
                             'last_access',
                         ]
                     ],
                     'links',
                 ]);

    }

    /**
     * @test POA recursos
     *
     */
    public function testGetPoaResourcesReturnsCorrectData()
    {


        // Crear recursos educativos simulados con accesos
        $resource1 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 1'
        ]);
        $resource2 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 2'
        ]);
        $resource3 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 3'
        ]);


        // Simular accesos a los recursos
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
        ]);
        // Realizar la solicitud a la ruta
        $response = $this->getJson(route('analytics-poa-resources', [
            'size' => 2,
            'sort' => [['field' => 'title', 'dir' => 'asc']]
        ]));

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'uid', // Asumiendo que tu modelo tiene un ID
                             'title',
                             'accesses_count',
                         ]
                     ],
                     'links',
                 ]);

        // Comprobar que los datos sean los esperados
        $this->assertEquals('Recurso 1', $response->json('data.0.title'));
        $this->assertEquals(2, $response->json('data.0.accesses_count'));
    }

    /**
     * @test POA recursos Order Desc
     *
     */
    public function testGetPoaResourcesSortDescData()
    {


        // Crear recursos educativos simulados con accesos
        $resource1 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 1'
        ]);
        $resource2 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 2'
        ]);
        $resource3 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 3'
        ]);


        // Simular accesos a los recursos
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
        ]);
        // Realizar la solicitud a la ruta
        $response = $this->getJson(route('analytics-poa-resources', [
            'size' => 2,

        ]));

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'uid', // Asumiendo que tu modelo tiene un ID
                             'title',
                             'accesses_count',
                         ]
                     ],
                     'links',
                 ]);

        // Comprobar que los datos sean los esperados
        $this->assertEquals('Recurso 1', $response->json('data.0.title'));
        $this->assertEquals(2, $response->json('data.0.accesses_count'));
    }

    public function testGetPoaResourcesAccesses()
    {

        // Crear recursos educativos simulados con accesos
        $resource1 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 1'
        ]);
        $resource2 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 2'
        ]);
        $resource3 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 3'
        ]);


        // Simular accesos a los recursos
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
        ]);

        // Realizar la solicitud a la ruta
        $response = $this->getJson(route('analytics-poa-resources-accesses', [
            'size' => 2,
            'sort' => [['field' => 'title', 'dir' => 'asc']]
        ]));

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'title',
                             'first_access',
                             'last_access',
                         ]
                     ],
                     'links',
                 ]);

        // Comprobar que los datos sean los esperados
        $this->assertEquals('Recurso 1', $response->json('data.0.title'));
    }

    public function testGetPoaResourcesAccessesWithPagination()
    {

        // Crear recursos educativos simulados con accesos
        $resource1 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 1'
        ]);
        $resource2 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 2'
        ]);
        $resource3 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 3'
        ]);


        // Simular accesos a los recursos
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
        ]);

        // Realizar la solicitud a la ruta con paginación
        $response = $this->getJson(route('analytics-poa-resources-accesses', [
            'size' => 2,
            'sort' => [['field' => 'first_access', 'dir' => 'desc']]
        ]));

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');

        // Comprobar que el recurso con el acceso más reciente esté primero
        $this->assertEquals('Recurso 1', $response->json('data.0.title'));
    }

    public function testGetPoaResourcesAccessesWithoutSort()
    {

        // Crear recursos educativos simulados con accesos
        $resource1 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 1'
        ]);
        $resource2 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 2'
        ]);
        $resource3 = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create([
            'uid' => generate_uuid(),
            'title' => 'Recurso 3'
        ]);


        // Simular accesos a los recursos
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(),'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
        ]);

        // Realizar la solicitud a la ruta sin parámetros de ordenamiento
        $response = $this->getJson(route('analytics-poa-resources-accesses'));

        // Verificar que se ordene por defecto por first_access descendente
        $response->assertStatus(200);

        // Comprobar que el recurso con el acceso más reciente esté primero
        $this->assertEquals('Recurso 1', $response->json('data.0.title'));

    }








}
