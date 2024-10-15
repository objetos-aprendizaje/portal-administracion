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
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    public function testIndexViewAnaliticsUsers()
    {

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
        $roles1 = UserRolesModel::firstOrCreate(['name' => 'Gestor','code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
        $roles2 = UserRolesModel::firstOrCreate(['name' => 'Administrador','code' => 'ADMNINISTRATOR'], ['uid' => generate_uuid()]);
        $user->roles()->attach($roles1->uid, ['uid' => generate_uuid()]);


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
        $roles1 = UserRolesModel::firstOrCreate(['name' => 'Gestor','code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
        $roles2 = UserRolesModel::firstOrCreate(['name' => 'Administrador','code' => 'ADMNINISTRATOR'], ['uid' => generate_uuid()]);

        // Define los parámetros de ordenación
        $sort = [
            ['field' => 'name', 'dir' => 'asc'], // Ordenar por el campo name
        ];

        // Realiza la solicitud a la ruta con parámetros de ordenación
        $response = $this->get(route('analytics-users-roles', ['sort' => $sort]));

        // Verifica que la respuesta sea un JSON y tenga el código de estado 200
        $response->assertStatus(200);

    }

    public function testGetStudentsReturnsJson()
    {
        // Crear algunos usuarios para la prueba
        UsersModel::factory()->count(5)->create();

        // Hacer una solicitud GET a la ruta
        $response = $this->getJson(route('analytics-students', [
            'size' => 10,
            'search' => 'John',
            'sort' => null,
        ]));

        // Comprobar que la respuesta es un JSON válido
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'current_page',
                     'data' => [
                         '*' => [ // Estructura esperada para cada usuario
                             'uid',
                             'first_name',
                             'last_name',
                             // Añade otros campos que esperas recibir
                         ],
                     ],
                     'last_page',
                     'per_page',
                     'total',
                 ]);
    }

    public function testGetStudentsWithSorting()
    {
        // Crear usuarios con nombres específicos
        UsersModel::factory()->create(['first_name' => 'Alice']);
        UsersModel::factory()->create(['first_name' => 'Bob']);

        // Hacer una solicitud GET con ordenamiento
        $response = $this->getJson(route('analytics-students', [
            'size' => 10,
            'search' => null,
            'sort' => [['field' => 'first_name', 'dir' => 'asc']],
        ]));

        // Comprobar que los resultados están ordenados correctamente
        $response->assertStatus(200)
                 ->assertJsonFragment(['first_name' => 'Alice'])
                 ->assertJsonFragment(['first_name' => 'Bob']);
    }

    public function testGetStudentsDataReturnsJson()
    {
        // Crear un usuario para la prueba
        $user = UsersModel::factory()->create()->first();

        $useraccess1 = UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::now()
        ]);
        $useraccess2 = UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(1)
        ]);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CoursesAccesesModel::factory()->create([
            'uid' =>generate_uuid(),
            'user_uid' => $user->uid,
            'access_date' => Carbon::today(),
            'course_uid' => $course->uid,
        ]);

        $educationalresource = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create();

        EducationalResourcesAccesesModel::factory()->create([
            'uid' =>generate_uuid(),'user_uid' => $user->uid,
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

        $useraccess1 = UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::now()
        ]);
        $useraccess2 = UsersAccessesModel::factory()->create([
            'user_uid' => $user->uid,
            'date' => Carbon::today()->subDays(1)
        ]);

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CoursesAccesesModel::factory()->create([
            'uid' =>generate_uuid(),
            'user_uid' => $user->uid,
            'access_date' => Carbon::today(),
            'course_uid' => $course->uid,
        ]);

        $educationalresource = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create();

        EducationalResourcesAccesesModel::factory()->create([
            'uid' =>generate_uuid(),'user_uid' => $user->uid,
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
            'uid' =>generate_uuid(),
            'user_uid' => $user->uid,
            'access_date' => Carbon::today(),
            'course_uid' => $course->uid,
        ]);

        $educationalresource = EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->withCreatorUser()->create();

        EducationalResourcesAccesesModel::factory()->create([
            'uid' =>generate_uuid(),'user_uid' => $user->uid,
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
