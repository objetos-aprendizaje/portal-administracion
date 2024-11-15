<?php

namespace Tests\Unit;

use App\Models\CategoriesModel;
use App\Models\CourseCategoriesModel;
use App\Models\CoursesAccesesModel;
use App\Models\CoursesStudentsModel;
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

class AnalyticsTopCategoriesControllerTest extends TestCase
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

    /**
     * @test
     * Prueba obtener las categorías principales con filtros, búsqueda y ordenamiento.
     */
    public function testGetTopCategoriesWithFiltersAndSorting()
    {
        // Crear categorías de prueba
        $category1 = CategoriesModel::factory()->create(['name' => 'Matemáticas']);
        $category2 = CategoriesModel::factory()->create(['name' => 'Ciencia']);

        // Crear estudiantes relacionados con las categorías
        $student1 = UsersModel::factory()->create();
        $student2 = UsersModel::factory()->create();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CourseCategoriesModel::factory()->create(['course_uid' => $course->uid, 'category_uid' => $category1->uid]);
        CourseCategoriesModel::factory()->create(['course_uid' => $course->uid, 'category_uid' => $category2->uid]);

        // Asignar estudiantes a las categorías
        CoursesStudentsModel::factory()->create([
            'user_uid' => $student1->uid,
            'course_uid' => $course->uid,
        ]);
        CoursesStudentsModel::factory()->create([
            'user_uid' => $student2->uid,
            'course_uid' => $course->uid,
        ]);

        // Crear filtros y ordenamientos
        $filters = [
            ['database_field' => 'categories.uid', 'value' => [$category1->uid, $category2->uid],],
            ['database_field' => 'acceptance_status', 'value' => ['ACCEPTED'],],
            ['database_field' => 'status', 'value' => ['ENROLLED'],],
            ['database_field' => 'created_at', 'value' => [now()->subDays(31), now()->subDays(15)],],

        ];
        $sort = [['field' => 'name', 'dir' => 'asc']];

        // Realizar la solicitud POST con filtros, búsqueda y ordenamiento
        $response = $this->postJson(route('get-top-categories'), [
            'size' => 2,
            'search' => 'Matemáticas',
            'sort' => $sort,
            'filters' => $filters,
        ]);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que los datos de la categoría están en la respuesta
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('Matemáticas', $responseData[0]['name']);
    }



    /** @test */
    public function testIndexLoadsTopCategoriesView()
    {
        // Realizar la solicitud al método index del controlador
        $response = $this->get('/analytics/top_categories'); // Asegúrate de que esta sea la ruta correcta

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que se carga la vista correcta
        $response->assertViewIs('analytics.top_categories.index');

        // Verificar que los datos pasados a la vista sean correctos
        $response->assertViewHas('page_name', 'TOP Categorias');
        $response->assertViewHas('page_title', 'TOP Categorias');
        $response->assertViewHas('resources', [
            "resources/js/analytics_module/analytics_top_categories.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'analytics-top-categories');
    }

    /**
     * @test
     * Prueba obtener los datos de categorías principales para el gráfico.
     */
    public function testGetTopCategoriesGraphWithFilters()
    {
        // Crear categorías de prueba
        $category1 = CategoriesModel::factory()->create(['name' => 'Historia']);
        $category2 = CategoriesModel::factory()->create(['name' => 'Geografía']);

        // Crear estudiantes relacionados con las categorías
        $student1 = UsersModel::factory()->create();
        $student2 = UsersModel::factory()->create();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        CourseCategoriesModel::factory()->create(['course_uid' => $course->uid, 'category_uid' => $category1->uid]);
        CourseCategoriesModel::factory()->create(['course_uid' => $course->uid, 'category_uid' => $category2->uid]);

        // Asignar estudiantes a las categorías
        CoursesStudentsModel::factory()->create(['user_uid' => $student1->uid, 'course_uid' => $course->uid, ]);
        
        CoursesStudentsModel::factory()->create(['user_uid' => $student2->uid, 'course_uid' => $course->uid,]);

        // Crear filtros
        $filters = [
            ['database_field' => 'categories.uid', 'value' => [$category1->uid, $category2->uid]],
            ['database_field' => 'created_at', 'value' => [now()->addDays(1)],],
        ];

        // Realizar la solicitud POST con filtros
        $response = $this->postJson(route('get-top-categories-graph'), [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que los datos de la categoría están en la respuesta
        $responseData = $response->json();
        $this->assertCount(2, $responseData); // Verificar que hay 2 categorías
        $this->assertEquals('Historia', $responseData[0]['name']);
        $this->assertEquals('Geografía', $responseData[1]['name']);
    }
}
