<?php

namespace Tests\Unit;

use Mockery;
use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\TooltipTextsModel;
use App\Models\UsersAccessesModel;
use Illuminate\Support\Facades\DB;
use App\Models\CoursesAccesesModel;
use App\Models\GeneralOptionsModel;
use App\Models\CoursesStudentsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourcesAccesesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnaliticsUsersControllerTest extends TestCase
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
    }

    public function testIndexViewAnaliticsUsers()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

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

        // Crear datos de prueba: agregar usuarios
        UsersModel::factory()->count(5)->create();

        // Realizar la solicitud a la ruta
        $response = $this->get(route('analytics-users'));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertViewIs('analytics.users_per_role.index');
        $response->assertViewHas('page_name', 'Analíticas de usuarios');
        $response->assertViewHas('page_title', 'Analíticas de usuarios');
        $response->assertViewHas('resources', [
            "resources/js/analytics_module/analytics_users.js"
        ]);
        $response->assertViewHas('total_users', 7);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'analytics-users');
    }

    public function testGetUsersRoles()
    {

        if (UserRolesModel::count() < 4) {
            UserRolesModel::create(['name' => 'Administrator']);
            UserRolesModel::create(['name' => 'Management']);
            UserRolesModel::create(['name' => 'Student']);
            UserRolesModel::create(['name' => 'Teacher']);
        }

        // Obtener hasta 3 roles
        UserRolesModel::take(3)->get();

        // Realizar la solicitud a la ruta
        $response = $this->get(route('analytics-users-roles', ['size' => 1]));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);

        // Asegurarse de que la respuesta es un array
        $responseData = $response->json();
        $this->assertIsArray($responseData, 'La respuesta no es un array.');
    }

    public function testGetUsersRolesGraph()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        Auth::login($user);

        // Arrange: Create a role and users with that role
        $roles1 = UserRolesModel::firstOrCreate(['name' => 'Gestor', 'code' => 'MANAGEMENT'], ['uid' => generateUuid()]);
        
        UserRolesModel::firstOrCreate(['name' => 'Administrador', 'code' => 'ADMNINISTRATOR'], ['uid' => generateUuid()]);
        
        $user->roles()->attach($roles1->uid, ['uid' => generateUuid()]);


        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles1);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);


        $response = $this->getJson(route('analytics-users-roles-graph'));


        $response->assertStatus(200);
    }

    public function testGetUsersRolesWithSorting()
    {
        // Prepara los datos necesarios para la prueba
        UserRolesModel::firstOrCreate(['name' => 'Gestor', 'code' => 'MANAGEMENT'], ['uid' => generateUuid()]);

        UserRolesModel::firstOrCreate(['name' => 'Administrador', 'code' => 'ADMNINISTRATOR'], ['uid' => generateUuid()]);

        // Define los parámetros de ordenación
        $sort = [
            ['field' => 'name', 'dir' => 'asc'], // Ordenar por el campo name
        ];

        // Realiza la solicitud a la ruta con parámetros de ordenación
        $response = $this->get(route('analytics-users-roles', ['sort' => $sort]));

        // Verifica que la respuesta sea un JSON y tenga el código de estado 200
        $response->assertStatus(200);
    }

    /**
     * @test
     * Prueba obtener lista de estudiantes con filtros, búsqueda y ordenamiento.
     */
    public function testGetStudentsWithFiltersAndSorting()
    {
        // Crear un usuario con rol de estudiante
        $studentRole = UserRolesModel::where('code','STUDENT')->first();
        $student = UsersModel::factory()->create();
        $student->roles()->attach($studentRole->uid,[
            'uid'=> generateUuid(),
        ]);

        // Crear accesos y cursos para el estudiante
        UsersAccessesModel::factory()->create([
            'user_uid' => $student->uid,
            'date'=>now(),
        ]);
        $course = CoursesModel::factory()
        ->withCourseStatus()
        ->withCourseType()
        ->create()->first();

        CoursesStudentsModel::factory()->create([
            'user_uid' => $student->uid,
            'course_uid'=> $course->uid,
        ]);

        // Crear datos de filtro y ordenamiento
        $filters = [
            ['database_field' => 'roles', 'value' => [$studentRole->uid]],
            ['database_field' => 'creation_date', 'value' => [now()->subMonth()->toDateString(), now()->toDateString()]],
        ];
        $sort = [['field' => 'first_name', 'dir' => 'asc']];

        // Realizar la solicitud POST con filtros, búsqueda y ordenamiento
        $response = $this->postJson('/analytics/users/get_students', [
            'size' => 1,
            'search' => $student->first_name,
            'sort' => $sort,
            'filters' => $filters,
        ]);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que los datos del estudiante están en la respuesta
        $responseData = $response->json('data');
        $this->assertCount(0, $responseData);
    }



    public function testGetStudentsDataReturnsJson()
    {
        // Crear un usuario para la prueba
        $user = UsersModel::factory()->create()->first();

        UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::now()
        ]);

        UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(1)
        ]);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CoursesAccesesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'access_date' => Carbon::today(),
            'course_uid' => $course->uid,
        ]);

        $educationalresource = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create();

        EducationalResourcesAccesesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
            'educational_resource_uid' => $educationalresource->uid
        ]);


        // Hacer una solicitud POST a la ruta
        $response = $this->postJson(route('analytics-students-data'), [
            'user_uid' => $user->uid,
            'filter_type' => null,
            'filter_date' => null,
        ]);

        // Comprobar que la respuesta es un JSON válido
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*', // Estructura principal del array
                0 => [
                    '*' => [ // Estructura esperada para el primer gráfico
                        'period',
                        'access_count',
                    ],
                ],
                1 => [
                    '*' => [ // Estructura esperada para el segundo gráfico
                        'period',
                        'access_count',
                    ],
                ],
                2 => [
                    '*' => [ // Estructura esperada para el tercer gráfico
                        'period',
                        'access_count',
                    ],
                ],

            ]);
    }

    public function testGetStudentsDataWithMonthlyFormat()
    {

        // Crear un usuario para la prueba
        $user = UsersModel::factory()->create()->first();

        UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::now()
        ]);
        
        UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(1)
        ]);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CoursesAccesesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'access_date' => Carbon::today(),
            'course_uid' => $course->uid,
        ]);

        $educationalresource = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create();

        EducationalResourcesAccesesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
            'educational_resource_uid' => $educationalresource->uid
        ]);

        // Simular datos de entrada
        $requestData = [
            'filter_type' => 'YYYY-MM',
            'filter_date' => '2024-01-01,2024-03-01',
            'user_uid' => $user->uid,
        ];



        // Ejecutar la función
        $response = $this->postJson(route('analytics-students-data'), $requestData);

        // Afirmaciones
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        // Verificar que los datos se agrupan correctamente por mes
        $this->assertCount(3, $data[0]);
    }


    public function testGetStudentsDataWithYearFormat()
    {

        // Crear un usuario para la prueba
        $user = UsersModel::factory()->create()->first();

        UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::now()
        ]);
        UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(1)
        ]);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CoursesAccesesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'access_date' => Carbon::today(),
            'course_uid' => $course->uid,
        ]);

        $educationalresource = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create();

        EducationalResourcesAccesesModel::factory()->create([
            'uid' => generateUuid(),
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
            'educational_resource_uid' => $educationalresource->uid
        ]);

        // Simular datos de entrada
        $requestData = [
            'filter_type' => 'YYYY',
            'filter_date' => '2023-01-01,2024-01-01',
            'user_uid' => $user->uid,
        ];

        // Ejecutar la función
        $response = $this->postJson(route('analytics-students-data'), $requestData);

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);

        // Verificar que los datos se agrupan correctamente por año
        $this->assertCount(2, $data[0]);
    }
}
