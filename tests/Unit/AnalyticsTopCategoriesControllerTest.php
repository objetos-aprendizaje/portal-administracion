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



 /** @test */
    public function testFilterCategoriesSearch()
    {
        $user1 = UsersModel::factory()->create()->latest()->first();
        $user2 = UsersModel::factory()->create()->latest()->first();

        // Preparar datos de prueba usando factories
        $category1 = CategoriesModel::factory()->create(['uid' => generate_uuid(),'name' => 'Mathematics']);
        $category2 = CategoriesModel::factory()->create(['uid' => generate_uuid(),'name' => 'Science']);
        $category3 = CategoriesModel::factory()->create(['uid' => generate_uuid(),'name' => 'History']);

        $course1 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['uid' => generate_uuid(),'title' => 'Course 1']);
        $course2 = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(['uid' => generate_uuid(),'title' => 'Course 2']);

        CourseCategoriesModel::factory()->create(['course_uid' => $course1->uid, 'category_uid' => $category1->uid]);
        CourseCategoriesModel::factory()->create(['course_uid' => $course1->uid, 'category_uid' => $category2->uid]);
        CourseCategoriesModel::factory()->create(['course_uid' => $course2->uid, 'category_uid' => $category2->uid]);
        CourseCategoriesModel::factory()->create(['course_uid' => $course2->uid, 'category_uid' => $category3->uid]);

        CoursesStudentsModel::factory()->create(['course_uid' => $course1->uid, 'user_uid' => $user1->uid]);
        CoursesStudentsModel::factory()->create(['course_uid' => $course2->uid, 'user_uid' => $user2->uid]);

        // Realizar la solicitud al endpoint con búsqueda
        $response = $this->getJson(route('get-top-categories', [
            'size' => 10,
            'search' => 'Math', // Búsqueda por parte del nombre
            'sort' => null,
        ]));

        // Verificar la respuesta
        $response->assertStatus(200)
                ->assertJsonCount(1, 'data'); // Solo debería devolver una categoría

        // Verificar que el resultado sea correcto
        $this->assertEquals('Mathematics', $response->json('data.0.name'));
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


}
