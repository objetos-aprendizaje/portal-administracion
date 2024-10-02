<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnaliticsAbandonedControllerTest extends TestCase
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


    /** @test Index Abandonar Curso*/
    public function testIndexReturnsAbandonedCourses()
    {
        // Act: Send a GET request to the specified route
        $response = $this->get(route('analytics-abandoned'));

        // Assert: Check that the response is successful and view is returned
        $response->assertStatus(200);
        $response->assertViewIs('analytics.abandoned.index');

        // Assert: Check that the view has the expected data
        $response->assertViewHas('page_name', 'Abandonos de cursos');
        $response->assertViewHas('page_title', 'Abandonos de cursos');
        $response->assertViewHas('resources', [
            'resources/js/analytics_module/analytics_abandoned.js',
            'resources/js/analytics_module/d3.js'
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'analytics-abandoned');
    }


     /** @test */
     public function testReturnsAbandonedGraphData()
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
             ['uid' => generate_uuid(),'user_uid' => $student1->uid, 'access_date' => now()->subDays(31)],
             ['uid' => generate_uuid(),'user_uid' => $student2->uid, 'access_date' => now()->subDays(10)],
         ]);

          // Attach students with additional pivot data
        $course->students()->syncWithoutDetaching([
            $student1->uid => [
                'uid' => generate_uuid(),
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ],
            $student2->uid => [
                'uid' => generate_uuid(),
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED'
            ]
        ]);

         // Act: Call the method directly or through a route if applicable
         $response = $this->get('/analytics/users/get_abandoned_graph'); // Adjust route name as needed

         // Assert: Check that the response is successful and contains expected data
         $response->assertStatus(200);

     }
}
