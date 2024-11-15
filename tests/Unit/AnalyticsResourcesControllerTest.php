<?php

namespace Tests\Unit;


use Carbon\Carbon;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\TooltipTextsModel;
use App\Models\CoursesVisitsModel;
use Illuminate\Support\Facades\DB;
use App\Models\CoursesAccesesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourcesAccesesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\EducationalResourcesEmbeddingsModel;

class AnalyticsResourcesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]); // Crea roles de prueba
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


    public function testIndexReturnsViewWithData()
    {
        // Realizar la solicitud a la ruta correspondiente al método index
        $response = $this->get('/analytics/resources');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertViewIs('analytics.resources.index') // Verifica que se carga la vista correcta
            ->assertViewHas('page_name', 'Analíticas de recursos') // Verifica el contenido de page_name
            ->assertViewHas('page_title', 'Analíticas de recursos') // Verifica el contenido de page_title
            ->assertViewHas('resources', [
                "resources/js/analytics_module/analytics_resources.js",
            ]) // Verifica que los recursos sean los esperados
            ->assertViewHas('tabulator', true) // Verifica el valor de tabulator
            ->assertViewHas('submenuselected', 'analytics-poa'); // Verifica el valor de submenuselected
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
            ['uid' => generate_uuid(), 'user_uid' => $student1->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(31)],
            ['uid' => generate_uuid(), 'user_uid' => $student2->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(10)],
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
            ['uid' => generate_uuid(), 'user_uid' => $student1->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(31)],
            ['uid' => generate_uuid(), 'user_uid' => $student2->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(10)],
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
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
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
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
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
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource1->uid, 'date' => now()],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource1->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource2->uid, 'date' => now()->subDays(2)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(3)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource3->uid, 'date' => now()->subDays(4)],
        ]);

        // Realizar la solicitud a la ruta sin parámetros de ordenamiento
        $response = $this->getJson(route('analytics-poa-resources-accesses'));

        // Verificar que se ordene por defecto por first_access descendente
        $response->assertStatus(200);

        // Comprobar que el recurso con el acceso más reciente esté primero
        $this->assertEquals('Recurso 1', $response->json('data.0.title'));
    }

    /**
     * @test
     * Prueba que se obtienen los datos de los recursos correctamente
     */
    public function testGetResourcesDataReturnsCorrectData()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Crear accesos simulados para el recurso educativo
        DB::table('educational_resource_access')->insert([
            'uid'                      => generate_uuid(),
            'educational_resource_uid' => $resource->uid,
            'date'                     => now()->subDays(5),
            'user_uid'                 => UsersModel::factory()->create()->first()->uid,
        ]);

        // Crear visitas simuladas para el recurso educativo
        DB::table('educational_resource_access')->insert([
            'uid'                      => generate_uuid(),
            'educational_resource_uid' => $resource->uid,
            'date'                     => now()->subDays(3),
            'user_uid'                 => null, // Para visitas anónimas
        ]);

        // Preparar los datos de la solicitud
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_type_resource'     => 'YYYY-MM-DD',
            'filter_date_resource'     => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que la respuesta contiene los datos correctos
        $responseData = $response->json();

        // Verificar que los accesos y visitas tienen los datos simulados correctamente
        $this->assertArrayHasKey('accesses', $responseData);
        $this->assertArrayHasKey('visits', $responseData);

        // Verificar que el último acceso tiene la información correcta
        $this->assertArrayHasKey('last_access', $responseData);
        $this->assertNotEmpty($responseData['last_access']['access_date']);

        // Verificar que el último acceso tiene el nombre del usuario
        $this->assertNotEmpty($responseData['last_access']['user_name']);

        // Verificar que el último acceso anónimo tiene la información correcta
        $this->assertArrayHasKey('last_visit', $responseData);
        $this->assertNotEmpty($responseData['last_visit']['access_date']);

        // Verificar que el número de usuarios diferentes es correcto
        $this->assertArrayHasKey('different_users', $responseData);
        // $this->assertEquals(0, $responseData['different_users']); // Ya que se creó solo un usuario

        // Verificar que la fecha de filtro es la correcta
        $this->assertEquals($requestData['filter_date_resource'], $responseData['filter_date']);
    }

    /**
     * @test
     * Prueba que se asigna el formato de fecha por defecto cuando filter_type_resource es null
     */
    public function testGetResourcesDataAssignsDefaultDateFormatWhenFilterTypeIsNull()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Crear accesos simulados para el recurso educativo
        DB::table('educational_resource_access')->insert([
            'uid'                      => generate_uuid(),
            'educational_resource_uid' => $resource->uid,
            'date' => now()->subDays(5),
            'user_uid' => UsersModel::factory()->create()->uid,
        ]);

        // Preparar los datos de la solicitud sin 'filter_type_resource'
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_type_resource'     => null,
            'filter_date_resource' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que la respuesta contiene el formato de fecha por defecto
        $responseData = $response->json();
        $this->assertEquals('YYYY-MM-DD', $responseData['date_format']);
    }

    /**
     * @test
     * Prueba que se asigna la semana actual cuando filter_date_resource es null
     */
    public function testGetResourcesDataAssignsCurrentWeekWhenFilterDateIsNull()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Crear accesos simulados para el recurso educativo
        DB::table('educational_resource_access')->insert([
            'uid'                      => generate_uuid(),
            'educational_resource_uid' => $resource->uid,
            'date'                     => now()->subDays(5),
            'user_uid'                 => UsersModel::factory()->create()->uid,
        ]);

        // Preparar los datos de la solicitud sin 'filter_date_resource'
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_type_resource' => 'YYYY-MM-DD',
            'filter_date_resource' => null
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Obtener la fecha del lunes y domingo de la semana actual
        $currentWeekStart = now()->startOfWeek()->format('Y-m-d');
        $currentWeekEnd = now()->endOfWeek()->format('Y-m-d');

        // Verificar que la fecha asignada en la respuesta es la semana actual
        $responseData = $response->json();
        $this->assertEquals($currentWeekStart . ',' . $currentWeekEnd, $responseData['filter_date']);
    }

    /**
     * @test
     * Prueba que calcula correctamente el max_access_count cuando hay accesos
     */
    public function testGetResourcesDataCalculatesMaxAccessCount()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Insertar accesos simulados
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource->uid, 'date' => now()->subDays(1), 'user_uid' => UsersModel::factory()->create()->uid],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource->uid, 'date' => now(), 'user_uid' => UsersModel::factory()->create()->uid],
        ]);

        // Preparar los datos de la solicitud
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_date_resource' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
            'filter_type_resource' => 'YYYY-MM-DD',
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que el max_access_count es mayor que cero
        $responseData = $response->json();
        $this->assertGreaterThan(0, $responseData['max_value']);
    }

    /**
     * @test
     * Prueba que calcula correctamente el max_access_count cuando hay accesos
     */
    public function testGetResourcesDataCalculatesMaxVisitsCount()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Insertar accesos simulados
        DB::table('educational_resource_access')->insert([
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource->uid, 'date' => now()->subDays(1)],
            ['uid' => generate_uuid(), 'educational_resource_uid' => $resource->uid, 'date' => now()],
        ]);

        // Preparar los datos de la solicitud
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_date_resource' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
            'filter_type_resource' => 'YYYY-MM-DD',
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que el max_access_count es mayor que cero
        $responseData = $response->json();
        $this->assertGreaterThan(0, $responseData['max_value']);
    }

    /**
     * @test
     * Prueba que el periodo se agrupa correctamente por meses
     */
    public function testGetResourcesDataGroupsByMonth()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->create();

        // Preparar los datos de la solicitud con 'filter_type_resource' como 'YYYY-MM'
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_date_resource' => now()->startOfYear()->format('Y-m-d') . ',' . now()->endOfYear()->format('Y-m-d'),
            'filter_type_resource' => 'YYYY-MM',
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que los datos están agrupados por meses
        $responseData = $response->json();
        $this->assertEquals('YYYY-MM', $responseData['date_format']);
    }

    /**
     * @test
     * Prueba que el periodo se agrupa correctamente por años
     */
    public function testGetResourcesDataGroupsByYear()
    {
        // Crear un recurso educativo simulado
        $resource = EducationalResourcesModel::factory()
            ->withStatus()
            ->withEducationalResourceType()
            ->withCreatorUser()->create();

        // Preparar los datos de la solicitud con 'filter_type_resource' como 'YYYY'
        $requestData = [
            'educational_resource_uid' => $resource->uid,
            'filter_date_resource' => now()->subYears(2)->startOfYear()->format('Y-m-d') . ',' . now()->endOfYear()->format('Y-m-d'),
            'filter_type_resource' => 'YYYY',
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-resources-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que los datos están agrupados por años
        $responseData = $response->json();
        $this->assertEquals('YYYY', $responseData['date_format']);
    }

    /**
     * @test
     * Prueba que se obtienen los datos de los cursos correctamente
     */
    public function testGetCoursesDataReturnsCorrectData()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create();

        // Crear accesos y visitas simulados para el curso
        CoursesAccesesModel::factory()->create([
            'course_uid' => $course->uid,
            'access_date' => now()->subDays(5),
            'user_uid' => UsersModel::factory()->create()->uid,
        ]);

        CoursesVisitsModel::factory()->create([
            'course_uid' => $course->uid,
            'access_date' => now()->subDays(3),
            'user_uid' => UsersModel::factory()->create()->uid,
        ]);



        // Preparar los datos de la solicitud
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY-MM-DD',
            'filter_date' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que la respuesta contiene los datos correctos
        $responseData = $response->json();

        // Verificar que los accesos y visitas tienen los datos simulados correctamente
        $this->assertArrayHasKey('accesses', $responseData);
        $this->assertArrayHasKey('visits', $responseData);

        // Verificar que el último acceso tiene la información correcta
        $this->assertArrayHasKey('last_access', $responseData);
        $this->assertNotEmpty($responseData['last_access']['access_date']);

        // Verificar que el último acceso tiene el nombre del usuario
        $this->assertNotEmpty($responseData['last_access']['user_name']);

        // Verificar que el número de usuarios diferentes es correcto
        $this->assertArrayHasKey('different_users', $responseData);
        $this->assertEquals(0, $responseData['different_users']); // Ya que se creó solo un usuario

        // Verificar que los usuarios inscritos son correctos
        $this->assertArrayHasKey('inscribed_users', $responseData);
    }

    /**
     * @test
     * Prueba que asigna el tipo de filtro por defecto 'YYYY-MM-DD' si no se proporciona.
     */
    public function testGetCoursesDataAssignsDefaultFilterType()
    {
        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create();

        // Preparar los datos de la solicitud sin el filtro 'filter_type'
        $requestData = [
            'course_uid' => $course->uid,
            'filter_date' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
            'filter_type'     => null,
            // No enviar 'filter_type'
        ];

        // Hacer la solicitud POST
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que se haya utilizado el valor por defecto 'YYYY-MM-DD'
        $responseData = $response->json();
        $this->assertEquals('YYYY-MM-DD', $responseData['date_format']);
    }

    /**
     * @test
     * Prueba que asigna la fecha por defecto de la semana actual si no se proporciona.
     */
    public function testGetCoursesDataAssignsDefaultFilterDate()
    {
        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create();

        // Preparar los datos de la solicitud sin 'filter_date'
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY-MM-DD',
            'filter_date' => null
        ];

        // Hacer la solicitud POST
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que se haya asignado la fecha de la semana actual
        $responseData = $response->json();
        $this->assertEquals(
            now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
            $responseData['filter_date']
        );
    }

    /**
     * @test
     * Prueba que calcula correctamente el max_access_count.
     */
    public function testGetCoursesDataCalculatesMaxAccessCount()
    {
        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create()
            ->first();

        // Insertar accesos simulados para que caigan dentro del rango de la semana actual
        DB::table('courses_accesses')->insert([
            ['uid' => generate_uuid(), 'user_uid' => UsersModel::factory()->create()->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(1)], // Acceso ayer
            ['uid' => generate_uuid(), 'user_uid' => UsersModel::factory()->create()->uid, 'course_uid' => $course->uid, 'access_date' => now()], // Acceso hoy
        ]);

        // Preparar los datos de la solicitud
        $requestData = [
            'course_uid' => $course->uid,
            'filter_date' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'), // Rango de fechas actual
            'filter_type' => 'YYYY-MM-DD',
        ];

        // Hacer la solicitud POST
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que el max_access_count es mayor que cero
        $responseData = $response->json();
        $this->assertGreaterThan(0, $responseData['max_value']);
    }


    /**
     * @test
     * Prueba que calcula correctamente el max_visits_count.
     */
    public function testGetCoursesDataCalculatesMaxVisitsCount()
    {
        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create();

        // Insertar visitas simuladas
        DB::table('courses_visits')->insert([
            ['uid' => generate_uuid(), 'user_uid' => UsersModel::factory()->create()->uid, 'course_uid' => $course->uid, 'access_date' => now()->subDays(1)],
            ['uid' => generate_uuid(), 'user_uid' => UsersModel::factory()->create()->uid,  'course_uid' => $course->uid, 'access_date' => now()],
        ]);

        // Preparar los datos de la solicitud
        $requestData = [
            'course_uid' => $course->uid,
            'filter_date' => now()->startOfWeek()->format('Y-m-d') . ',' . now()->endOfWeek()->format('Y-m-d'),
            'filter_type' => 'YYYY-MM-DD',
        ];

        // Hacer la solicitud POST
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que el max_visits_count es mayor que cero
        $responseData = $response->json();
        $this->assertGreaterThan(0, $responseData['max_value']);
    }

    /**
     * @test
     * Prueba que el formato de fechas es YYYY-MM cuando filter_type es 'YYYY-MM'.
     */
    public function testGetCoursesDataUsesMonthFormatForFilterTypeYYYYMM()
    {
        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create();

        // Preparar los datos de la solicitud con filter_type 'YYYY-MM'
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY-MM',
            'filter_date' => now()->startOfYear()->format('Y-m-d') . ',' . now()->endOfYear()->format('Y-m-d'),
        ];

        // Hacer la solicitud POST
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que las fechas se agrupan por meses en el formato 'Y-m'
        $responseData = $response->json();
        $this->assertEquals('YYYY-MM', $responseData['date_format']);
        foreach ($responseData['accesses'][0] as $access) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $access['access_date_group']); // Formato 'Y-m'
        }
    }

    /**
     * @test
     * Prueba que el formato de fechas es YYYY cuando filter_type es 'YYYY'.
     */
    public function testGetCoursesDataUsesYearFormatForFilterTypeYYYY()
    {
        // Crear un curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create();

        // Preparar los datos de la solicitud con filter_type 'YYYY'
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY',
            'filter_date' => now()->startOfDecade()->format('Y-m-d') . ',' . now()->endOfDecade()->format('Y-m-d'),
        ];

        // Hacer la solicitud POST
        $response = $this->post(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que las fechas se agrupan por años en el formato 'Y'
        $responseData = $response->json();
        $this->assertEquals('YYYY', $responseData['date_format']);
        foreach ($responseData['accesses'][0] as $access) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $access['access_date_group']); // Formato 'Y'
        }
    }


    /**
     * @test
     * Prueba que se aplican correctamente los filtros con embeddings nulos y otros campos en el gráfico de recursos educativos (POA Graph Resources).
     */
    public function testGetPoaGraphResourcesWithNullEmbeddingsAndOtherFieldFilters()
    {
        // Crear categorías simuladas
        $category1 = CategoriesModel::factory()->create();
        $category2 = CategoriesModel::factory()->create();

        // Crear un array de 1536 dimensiones para embeddings

        $embeddingVector = '[' . implode(',', array_fill(0, 1536, '0.1')) . ']';

        // Crear recursos educativos simulados, algunos con embeddings y otros sin embeddings
        $resourceWithEmbeddings = EducationalResourcesModel::factory()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->withStatus()->create();

        EducationalResourcesEmbeddingsModel::factory()->create(
            [
                'educational_resource_uid'=> $resourceWithEmbeddings->uid,
                'embeddings'=> null,
            ]
        );

        $resourceWithoutEmbeddings = EducationalResourcesModel::factory()
            ->withEducationalResourceType()
            ->withCreatorUser()
            ->withStatus()->create();

            EducationalResourcesEmbeddingsModel::factory()->create(
                [
                    'educational_resource_uid'=> $resourceWithoutEmbeddings->uid,
                    'embeddings'=> null,

                ]
            );

        // Asignar categorías a los recursos
        $resourceWithEmbeddings->categories()->attach($category1->uid, [
            'uid' => generate_uuid(),
        ]);
        $resourceWithoutEmbeddings->categories()->attach($category2->uid, [
            'uid' => generate_uuid(),
        ]);

        // Insertar accesos simulados
        EducationalResourcesAccesesModel::factory()->count(10)->create([
            'educational_resource_uid' => $resourceWithEmbeddings->uid,
            'date' => Carbon::now(),
        ]);
        EducationalResourcesAccesesModel::factory()->count(5)->create([
            'educational_resource_uid' => $resourceWithoutEmbeddings->uid,
            'date' => Carbon::now(),
        ]);

        // Preparar los filtros simulados para buscar recursos sin embeddings y por categoría
        $filters = [
            ['database_field' => 'categories', 'value' => [$category2->uid]], // Filtrar por categoría 2
            // ['database_field' => 'embeddings', 'value' => 0], // Filtrar por recursos sin embeddings (whereNull)
        ];

        // Hacer la solicitud POST con los filtros
        $response = $this->postJson(route('analytics-poa-graph-resources'), [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que los datos están presentes en la respuesta
        $data = $response->json();

        // Verificar que solo se devuelven recursos sin embeddings y pertenecientes a la categoría 2
        $this->assertNotEmpty($data);
        // foreach ($data as $resource) {
        //     $this->assertNull($resource['embeddings']);
        //     // $this->assertTrue(in_array($category2->uid, array_column($resource['categories'], 'uid')));
        // }

        // Probar el filtro con un campo genérico de la base de datos (por ejemplo, 'title')
        $filters = [
            ['database_field' => 'title', 'value' => $resourceWithEmbeddings->title], // Filtrar por título del recurso con embeddings
        ];

        $response = $this->postJson(route('analytics-poa-graph-resources'), [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que solo se devuelve el recurso con el título específico
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($resourceWithEmbeddings->title, $data[0]['title']);
    }
   

    /**
     * @test
     * Prueba que el método getPoaResources devuelve los recursos correctamente, con búsqueda, ordenamiento y filtros.
     */
    public function testGetPoaResourcesWithSearchSortAndFilters()
    {

        // Crear categorías simuladas
        $category1 = CategoriesModel::factory()->create();
        $category2 = CategoriesModel::factory()->create();

        // Crear recursos educativos simulados con accesos y visitas
        $resource1 = EducationalResourcesModel::factory()
            ->withStatus()->withEducationalResourceType()->withCreatorUser()
            // ->withAccesses(10)
            // ->withVisits(5)
            ->create(['title' => 'Resource 1']);

        $resource2 = EducationalResourcesModel::factory()
            // ->withAccesses(15)
            // ->withVisits(2)
            ->withStatus()->withEducationalResourceType()->withCreatorUser()
            ->create(['title' => 'Resource 2']);

        $resource3 = EducationalResourcesModel::factory()
            // ->withAccesses(20)
            // ->withVisits(7)
            ->withStatus()->withEducationalResourceType()->withCreatorUser()
            ->create(['title' => 'Resource 3']);

        $resource1->categories()->attach($category1->uid, [
            'uid' => generate_uuid(),
        ]);
        $resource2->categories()->attach($category2->uid, [
            'uid' => generate_uuid(),
        ]);

        // Preparar los filtros simulados, por ejemplo, filtrar por categoría
        $filters = [
            ['database_field' => 'categories', 'value' => [$category1->uid, $category2->uid]],
        ];

        // Preparar los datos de la solicitud
        $requestData = [
            'size' => 2, // Paginación con 2 elementos por página
            'search' => 'Resource', // Búsqueda por título
            'sort' => [
                ['field' => 'accesses_count', 'dir' => 'asc'], // Ordenar por número de accesos ascendente
            ],
            'filters' => $filters, // Filtros aplicados
        ];

        // Hacer la solicitud POST a la ruta correspondiente
        $response = $this->postJson(route('analytics-poa-resources'), $requestData);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contiene datos paginados
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']); // Deberían haber 2 elementos debido al tamaño de la paginación

        // Verificar que los resultados están ordenados por accesos de manera ascendente
        $this->assertLessThanOrEqual(
            $data['data'][1]['accesses_count'],
            $data['data'][0]['accesses_count'],
            'Los recursos no están ordenados correctamente por accesos ascendentes.'
        );

        // Verificar que el título contiene el término de búsqueda
        foreach ($data['data'] as $resource) {
            $this->assertStringContainsString('Resource', $resource['title']);
        }

        // Probar si se aplica correctamente el filtro por categoría (esto depende de los filtros que se han definido)
        $this->assertNotEmpty($data['data']); // Asegurarse de que haya resultados con los filtros aplicados
    }

}
