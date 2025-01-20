<?php

namespace Tests\Unit;

use App\User;
use DateTime;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\CourseStatusesModel;
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
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
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
            "resources/js/analytics_module/analytics_abandoned.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'analytics-abandoned');
    }

    /**
     * @test Gráfico de cursos abandonados con estudiantes y accesos validados por fecha
     */
    public function testGetAbandonedGraphWithEnrolledAcceptedStudentsAndAccessCheck()
    {
        // Configurar el umbral de abandono
        $mockGeneralOptions = ['threshold_abandoned_courses' => 30];
        app()->instance('general_options', $mockGeneralOptions);

        $status = CourseStatusesModel::where('code','DEVELOPMENT')->first();

        // Crear curso en estado "DEVELOPMENT" con una fecha de inicio antigua
        $course = CoursesModel::factory()
            ->withCourseType()
            ->create([
                'course_status_uid'=>$status->uid,
                'realization_start_date' => now()->subDays(60),
                'lms_url' => 'https://example-lms-url.com'
            ]);

        // Crear estudiante en estado 'ENROLLED' y 'ACCEPTED' vinculado al curso
        $student = UsersModel::factory()->create();
        $course->students()->attach($student->uid, [
            'status' => 'ENROLLED',
            'acceptance_status' => 'ACCEPTED',
            'uid' => generateUuid(),
        ]);

        // Simular acceso del estudiante hace 40 días
        $course->accesses()->create([
            'uid'=> generateUuid(),
            'user_uid' => $student->uid,
            'access_date' => now()->subDays(40),
        ]);

        // Actuar como el usuario y realizar la solicitud GET
        $this->actingAs($student);
        $response = $this->getJson(route('analytics-abandoned-graph'));

        // Verificar estado de la respuesta
        $response->assertStatus(200);
        $responseData = $response->json();

        // Obtener la fecha de abandono y la fecha de hoy para verificación
        $fechaHoy = new DateTime();
        $fechaAbandono = new DateTime($responseData[0]['abandoned_date']);

        // Validar lógica de abandono
        if ($fechaHoy <= $fechaAbandono) {
            // Si aún no se ha alcanzado la fecha de abandono
            $this->assertEquals(0, $responseData[0]['abandoned']);
        } else {
            // Verificar abandonos cuando la fecha de abandono ha pasado
            $this->assertEquals($responseData[0]['enrolled_accepted_students_count'], $responseData[0]['abandoned']);
        }

        // Asegurar estructura de respuesta para `abandoned_users`
        $this->assertArrayHasKey('abandoned_users', $responseData[0]);
        $this->assertIsArray($responseData[0]['abandoned_users']);
    }

    /**
     * @test Actualizar el umbral de cursos abandonados.
     */
    public function testSaveThresholdAbandonedCourses()
    {
        // Crea una instancia de la opción general para el umbral de cursos abandonados
        GeneralOptionsModel::factory()->create([
            'option_name' => 'threshold_abandoned_courses',
            'option_value' => 5
        ]);

        // Define el nuevo valor del umbral
        $newThreshold = 10;

        // Realiza la solicitud POST con el valor válido del umbral
        $response = $this->postJson('/analytics/abandoned/save_threshold_abandoned_courses', [
            'threshold_abandoned_courses' => $newThreshold
        ]);

        // Verifica que la respuesta tenga el estado 200
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Umbral actualizado correctamente']);

        // Verifica que el valor del umbral haya sido actualizado en la base de datos
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'threshold_abandoned_courses',
            'option_value' => (string)$newThreshold
        ]);
    }

    /**
     * @test Validación para valor no numérico o negativo.
     */
    public function testSaveThresholdAbandonedCoursesWithInvalidValue()
    {
        // Valores inválidos de prueba
        $invalidValues = ['texto', -1];

        foreach ($invalidValues as $invalidValue) {
            $response = $this->postJson('/analytics/abandoned/save_threshold_abandoned_courses', [
                'threshold_abandoned_courses' => $invalidValue
            ]);

            // Verifica que la respuesta tenga el estado de error
            $response->assertStatus(406);
            $response->assertJson(['message' => 'El número introducido no es válido']);
        }
    }
}
