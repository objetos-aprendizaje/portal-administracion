<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\CourseTypesModel;
use App\Models\CoursesVisitsModel;
use App\Models\CoursesAccesesModel;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\View;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsCoursesControllerTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     * Prueba para verificar que la vista de analíticas de cursos se carga correctamente.
     */

    public function testIndexAnalyticsCourses22()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );

        $this->actingAs($user);
        // Simular datos en la base de datos
        CourseStatusesModel::all();
        CallsModel::factory()->count(2)->create();
        EducationalProgramTypesModel::factory()->count(4)->create();
        CourseTypesModel::factory()->count(5)->create();
        CategoriesModel::factory()->count(3)->create();
        CentersModel::factory()->count(1)->create();

        $teacherRole = UserRolesModel::where('code', 'TEACHER')->first();
        $studentRole = UserRolesModel::where('code', 'STUDENT')->first();

        $teacher = UsersModel::factory()->create();
        $student = UsersModel::factory()->create();


        $teacher->roles()->attach(
            $teacherRole->uid,
            [
                'uid' => generateUuid()
            ]
        );
        $student->roles()->attach(
            $studentRole->uid,
            [
                'uid' => generateUuid()
            ]
        );

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        // Realizar la solicitud a la ruta
        $response = $this->get(route('analytics-courses'));

        // Verificar que la respuesta sea exitosa y contenga la vista correcta
        $response->assertStatus(200);
        $response->assertViewIs('analytics.courses.index');

        // Verificar que los datos necesarios estén presentes en la vista
        $response->assertViewHas('courses_statuses');
        $response->assertViewHas('calls');

        $response->assertViewHas('courses_types');
        $response->assertViewHas('categories');
        $response->assertViewHas('teachers');
        $response->assertViewHas('students');
        $response->assertViewHas('centers');
    }

    /**
     * @test
     * Verifica que el gráfico de estatus de los cursos se genera correctamente.
     */
    public function testGetCoursesStatusesGraph()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );

        $this->actingAs($user);

        // Crea un conjunto de estatus de cursos simulados
        $status1 = CourseStatusesModel::factory()->create([
            'name' => 'Status 1',
            'code' => 'STATUS1',
        ]);

        $status2 = CourseStatusesModel::factory()->create([
            'name' => 'Status 2',
            'code' => 'STATUS2',
        ]);

        // Simula algunos cursos asociados a los estatus
        CoursesModel::factory()->withCourseType()->count(3)->create([
            'course_status_uid' => $status1->uid,

        ]);

        CoursesModel::factory()->withCourseType()->count(2)->create([
            'course_status_uid' => $status2->uid,
        ]);

        // Realiza la solicitud GET a la ruta correcta
        $response = $this->getJson(route('analytics-courses-statuses-graph-get'));

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);
    }

    /**
     * @test
     * Verifica que se obtienen los datos correctos del gráfico de POA aplicando los filtros.
     */
    public function testGetPoaGraphWithFilters()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );
        $this->actingAs($user);

        // Crear cursos simulados
        $course1 = CoursesModel::factory()
            ->withCourseStatus()->withCourseType()->create([
                'title' => 'Curso de Programación',
                'inscription_start_date' => now()->subMonths(3),
                'inscription_finish_date' => now()->subMonths(2),
                'realization_start_date' => now()->subMonths(2),
                'realization_finish_date' => now()->subMonth(),
                'creator_user_uid' => $user->uid,
            ]);

        $course2 = CoursesModel::factory()
            ->withCourseStatus()->withCourseType()->create([
                'title' => 'Curso de Diseño',
                'inscription_start_date' => now()->subMonths(4),
                'inscription_finish_date' => now()->subMonths(3),
                'realization_start_date' => now()->subMonths(3),
                'realization_finish_date' => now()->subMonths(1),
                'creator_user_uid' => $user->uid,
            ]);

        // Insertar accesos simulados
        CoursesAccesesModel::factory()->create([
            'course_uid' => $course1->uid,
            'user_uid' => $user->uid,
            'access_date' => now(),
        ]);

        CoursesAccesesModel::factory()->create([
            'course_uid' => $course2->uid,
            'user_uid' => $user->uid,
            'access_date' => now(),
        ]);

        $course1->teachers()->attach($user->uid, [
            'uid' => generateUuid(),
        ]);

        $course2->teachers()->attach($user->uid, [
            'uid' => generateUuid(),
        ]);

        // Crear un filtro de ejemplo
        $filters = [
            [
                'database_field' => 'creator_user_uid',
                'value' => [$user->uid]
            ],
            [
                'database_field' => 'inscription_date',
                'value' => [now()->subMonths(4)->format('Y-m-d'), now()->subMonths(2)->format('Y-m-d')]
            ],
            [
                'database_field' => 'realization_date',
                'value' => [now()->subMonths(4)->format('Y-m-d'), now()->subMonths(2)->format('Y-m-d')]
            ],
            [
                'database_field' => 'coordinators_teachers',
                'value' => [$user->uid]
            ],
            [
                'database_field' => 'no_coordinators_teachers',
                'value' => [$user->uid]
            ],
            [
                'database_field' => 'categories',
                'value' => [generateUuid()]
            ],
            [
                'database_field' => 'course_statuses',
                'value' => [$course1->course_status_uid, $course2->course_status_uid,]
            ],
            [
                'database_field' => 'calls',
                'value' => [generateUuid()]
            ],
            [
                'database_field' => 'course_types',
                'value' => [$course1->course_type_uid, $course2->course_type_uid]
            ],
            [
                'database_field' => 'min_required_students',
                'value' => 1
            ],
            [
                'database_field' => 'max_required_students',
                'value' => 2
            ],
            [
                'database_field' => 'min_ects_workload',
                'value' => 2
            ],
            [
                'database_field' => 'max_ects_workload',
                'value' => 3
            ],
            [
                'database_field' => 'min_cost',
                'value' => 10
            ],
            [
                'database_field' => 'max_cost',
                'value' => 15
            ],
            [
                'database_field' => 'learning_results',
                'value' => [generateUuid()]
            ],
            [
                'database_field' => 'title',
                'value' => 'Mi titulo'
            ],


        ];

        // Realizar la solicitud POST a la ruta con los filtros
        $response = $this->postJson(route('analytics-poa-graph'), [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
    }

    /**
     * @test
     * Prueba para obtener los datos de un curso filtrado por fechas
     */
    public function testGetCoursesDataReturnsCorrectCourseData()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );
        $this->actingAs($user);

        // Crear curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create([
                'title' => 'Curso de Prueba',
            ]);

        CoursesAccesesModel::factory()->create([
            'course_uid' => $course->uid,
            'user_uid' => $user->uid,
            'access_date' => now()->subDays(3),
        ]);

        CoursesVisitsModel::factory()->create(
            [
                'course_uid' => $course->uid,
                'user_uid' => $user->uid,
                'access_date' => now()->subDays(2),
            ]
        );
        $course->students()->attach($user->uid, [
            'uid' => generateUuid(),
        ]);

        // Datos de la solicitud con filtro de fecha
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY-MM-DD',
            'filter_date' => now()->subWeek()->format('Y-m-d') . ',' . now()->format('Y-m-d'),
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->postJson('/analytics/courses/get_courses_data', $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la respuesta contiene los datos correctos del curso
        $responseData = $response->json();
        $this->assertArrayHasKey('accesses', $responseData);
        $this->assertArrayHasKey('visits', $responseData);
        $this->assertArrayHasKey('last_access', $responseData);
        $this->assertArrayHasKey('last_visit', $responseData);
        $this->assertArrayHasKey('different_users', $responseData);
        $this->assertArrayHasKey('inscribed_users', $responseData);

        // Verificar que el número de estudiantes inscritos es correcto
        $this->assertEquals(1, $responseData['inscribed_users']);

        // Verificar que el máximo valor (accesses o visitas) es mayor que 0
        $this->assertGreaterThan(0, $responseData['max_value']);
    }

    /**
     * @test
     * Prueba para verificar el manejo de tipo de filtro y fecha en los datos del curso
     */
    public function testGetCoursesDataWithDefaultFilters()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );
        $this->actingAs($user);

        // Crear curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create([
                'title' => 'Curso de Prueba',
            ]);

        CoursesAccesesModel::factory()->create([
            'course_uid' => $course->uid,
            'user_uid' => $user->uid,
            'access_date' => now()->subDays(3),
        ]);

        CoursesVisitsModel::factory()->create(
            [
                'course_uid' => $course->uid,
                'user_uid' => $user->uid,
                'access_date' => now()->subDays(2),
            ]
        );
        $course->students()->attach($user->uid, [
            'uid' => generateUuid(),
        ]);

        // Datos de la solicitud sin especificar filtro de tipo o fecha
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => null,
            'filter_date' => null,
        ];

        // Realizar la solicitud POST a la ruta
        $response = $this->postJson('/analytics/courses/get_courses_data', $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la respuesta contiene los datos correctos del curso
        $responseData = $response->json();
        $this->assertArrayHasKey('accesses', $responseData);
        $this->assertArrayHasKey('visits', $responseData);
        $this->assertArrayHasKey('last_access', $responseData);
        $this->assertArrayHasKey('last_visit', $responseData);
        $this->assertArrayHasKey('different_users', $responseData);
        $this->assertArrayHasKey('inscribed_users', $responseData);

        // Verificar que el formato de fecha por defecto es 'YYYY-MM-DD'
        $this->assertEquals('YYYY-MM-DD', $responseData['date_format']);

        // Verificar que el filtro de fecha por defecto corresponde a la semana actual
        $hoy = Carbon::today();
        $lunes = $hoy->copy()->startOfWeek()->format('Y-m-d');
        $domingo = $hoy->copy()->endOfWeek()->format('Y-m-d');
        $this->assertEquals("{$lunes},{$domingo}", $responseData['filter_date']);

        // Verificar que el número de estudiantes inscritos es correcto
        $this->assertEquals(1, $responseData['inscribed_users']);

    }

    /**
     * @test
     * Prueba para verificar el filtrado por meses y años
     */
    public function testGetCoursesDataWithMonthAndYearFilters()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );
        $this->actingAs($user);


        // Crear curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create([
                'title' => 'Curso de Prueba',
            ]);

        // Datos de la solicitud con filtro de tipo 'YYYY-MM'
        $requestDataMonth = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY-MM',
            'filter_date' => now()->subYear()->format('Y-m') . ',' . now()->format('Y-m'),
        ];

        // Realizar la solicitud POST a la ruta con filtro por meses
        $responseMonth = $this->postJson('/analytics/courses/get_courses_data', $requestDataMonth);

        // Verificar que el formato de fecha es 'YYYY-MM'
        $responseMonth->assertStatus(200);
        $responseDataMonth = $responseMonth->json();
        $this->assertEquals('YYYY-MM', $responseDataMonth['date_format']);

        // Datos de la solicitud con filtro de tipo 'YYYY'
        $requestDataYear = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY',
            'filter_date' => now()->subYears(2)->format('Y') . ',' . now()->format('Y'),
        ];

        // Realizar la solicitud POST a la ruta con filtro por años
        $responseYear = $this->postJson(route('analytics-courses-data'), $requestDataYear);

        // Verificar que el formato de fecha es 'YYYY'
        $responseYear->assertStatus(200);
        $responseDataYear = $responseYear->json();
        $this->assertEquals('YYYY', $responseDataYear['date_format']);
    }
    /**
     * @test
     * Prueba para verificar el filtrado por años
     */
    public function testGetCoursesDataWithYearFilter()
    {
        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );
        $this->actingAs($user);

        // Crear curso simulado
        $course = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create([
                'title' => 'Curso de Prueba',
            ]);

        CoursesAccesesModel::factory()->create([
            'course_uid' => $course->uid,
            'user_uid' => $user->uid,
            'access_date' => now()->subDays(3),
        ]);

        CoursesVisitsModel::factory()->create(
            [
                'course_uid' => $course->uid,
                'user_uid' => $user->uid,
                'access_date' => now()->subDays(2),
            ]
        );
        $course->students()->attach($user->uid, [
            'uid' => generateUuid(),
        ]);

        // Datos de la solicitud con filtro de tipo 'YYYY'
        $requestData = [
            'course_uid' => $course->uid,
            'filter_type' => 'YYYY',
            'filter_date' => now()->subYears(3)->format('Y') . ',' . now()->format('Y'),
        ];

        // Realizar la solicitud POST a la ruta con filtro por años
        $response = $this->postJson(route('analytics-courses-data'), $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que el formato de fecha es 'YYYY'
        $responseData = $response->json();
        $this->assertEquals('YYYY', $responseData['date_format']);

        // Verificar que los accesos y visitas están agrupados correctamente por año
        foreach ($responseData['accesses'][0] as $access) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $access['access_date_group']);
        }

        foreach ($responseData['visits'][0] as $visit) {
            $this->assertMatchesRegularExpression('/^\d{4}$/', $visit['access_date_group']);
        }
    }

    /**
     * @test
     * Prueba para obtener los cursos con búsqueda, filtros y ordenamiento
     */
    public function testGetCoursesWithSearchSortAndFilters()
    {

        $user = UsersModel::factory()->create();
        $adminRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach(
            $adminRole->uid,
            [
                'uid' => generateUuid()
            ]
        );

        $this->actingAs($user);

        // Crear datos simulados de cursos
        $course1 = CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create([
                'title' => 'Curso de Programación',
            ]);

        CoursesAccesesModel::factory()->create([
            'course_uid' => $course1->uid,
            'user_uid' => $user->uid,
            'access_date' => now(),
        ]);

        CoursesVisitsModel::factory()->create(
            [
                'course_uid' => $course1->uid,
                'user_uid' => $user->uid,
                'access_date' => now(),
            ]
        );

        $course1->students()->attach($user->uid, [
            'uid' => generateUuid(),
        ]);

        // Filtros, búsqueda y ordenamiento
        $requestData = [
            'size' => 2,
            'search' => 'Programación',
            'sort' => [
                ['field' => 'visits_count', 'dir' => 'asc'],
            ],
            'filters' => [
                [
                    'database_field' => 'inscription_date',
                    'value' => [now()->subMonths(4)->format('Y-m-d'), now()->subMonths(2)->format('Y-m-d')]
                ],
            ]
        ];

        // Hacer la solicitud POST a la ruta
        $response = $this->postJson(route('analytics-courses-get'), $requestData);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la respuesta contiene los cursos filtrados correctamente
        $responseData = $response->json();
        $this->assertCount(0, $responseData['data']);
    }
}
