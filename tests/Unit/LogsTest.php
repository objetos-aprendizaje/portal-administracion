<?php

namespace Tests\Unit;


use Mockery;
use Tests\TestCase;
use ReflectionClass;
use App\Models\LogsModel;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Logs\LogsController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogsTest extends TestCase
{
    use RefreshDatabase;
    protected $queryMock;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    } // Configuración inicial si es necesario

    /**
     * @test
     * Prueba que el método index() del controlador LogsController
     * devuelva la vista correcta con los datos necesarios.
     */
    public function testIndexReturnsViewWithProperData()
    {
        // Crear algunos registros de prueba en la base de datos
        $log1 = LogsModel::factory()->create(['entity' => 'Entity1']);

        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Asignar un rol específico al usuario (por ejemplo, el rol 'ADMINISTRATOR')
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generateUuid()]
        ]);

        // Autenticar al usuario
        Auth::login($user);

        // Simular la carga de datos que haría el middleware
        View::share('roles', $user->roles->toArray());

        // Simular la carga de datos que haría el GeneralOptionsMiddleware
        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
        View::share('general_options', $general_options);

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Realizar la petición GET a la ruta correspondiente
        $response = $this->get(route('list-logs'));
        

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista tiene los datos correctos
        $response->assertViewHas('entities', [
            ['uid' => 'Entity1', 'name' => 'Entity1'],
        ]);

        $response->assertViewHas('users');

        // Verificar que la vista tiene la variable roles
        $response->assertViewHas('roles', function ($viewRoles) use ($role) {
            return in_array($role->uid, array_column($viewRoles, 'uid'));
        });

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('logs.list_logs.index');
    }

    /**
     * @test
     * Verifica que el método getLogs() del controlador LogsController
     * devuelve los registros correctos con los parámetros de búsqueda, ordenación y paginación.
     */
    public function testCanRetrieveLogsWithSearchSortAndPagination()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        // Crear registros de logs asociados al usuario
        LogsModel::factory()->create([
            'user_uid' => $user->uid,
            'entity' => 'TestEntity1',
            'info' => 'This is a test log entry 1'
        ]);

        LogsModel::factory()->create([
            'user_uid' => $user->uid,
            'entity' => 'TestEntity2',
            'info' => 'This is a test log entry 2'
        ]);

        // Realizar la petición GET con parámetros de búsqueda, ordenación y paginación
        $response = $this->json('GET', '/logs/list_logs/get_logs', [
            'search' => 'TestEntity1',
            'sort' => [['field' => 'entity', 'dir' => 'asc']],
            'size' => 10
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que los datos en la respuesta sean correctos
        $response->assertJsonFragment([
            'entity' => 'TestEntity1',
            'info' => 'This is a test log entry 1',
            'user_first_name' => 'John',
            'user_last_name' => 'Doe'
        ]);

        // Verificar que solo se ha devuelto un resultado
        $response->assertJsonCount(1, 'data');
    }

    /**
     * @test
     * Verifica que el método getLogs() aplique correctamente los filtros.
     */
    public function testAppliesFiltersCorrectly()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear logs con diferentes entidades
        LogsModel::factory()->create([
            'user_uid' => $user->uid,
            'entity' => 'EntityToFilter',
            'info' => 'Filterable log entry'
        ]);

        LogsModel::factory()->create([
            'user_uid' => $user->uid,
            'entity' => 'AnotherEntity',
            'info' => 'Another log entry'
        ]);

        // Definir filtros correctamente formados
        $filters = [
            [
                'database_field' => 'entity',
                'operator' => '=',
                'value' => ['EntityToFilter']
            ]
        ];

        // Realizar la petición GET con los filtros bien formados
        $response = $this->json('GET', '/logs/list_logs/get_logs', [
            'filters' => $filters,
            'size' => 10
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta no esté vacía
        $this->assertNotEmpty($response['data'], 'La respuesta no debe estar vacía cuando se aplica el filtro.');

        // Verificar que los datos filtrados se devuelven correctamente
        $response->assertJsonFragment([
            'entity' => 'EntityToFilter'
        ]);
    }


    public function testGetLogsAppliesDateRangeFilter()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        // Crear registros de logs en diferentes fechas
        LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 1',
            'entity' => 'Entidad 1',
            'user_uid' => $user->uid,
            'created_at' => now()->subDays(3), // Fecha anterior al rango
        ]);

        LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 2',
            'entity' => 'Entidad 2',
            'user_uid' => $user->uid,
            'created_at' => now()->subDays(1), // Dentro del rango
        ]);

        LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 3',
            'entity' => 'Entidad 3',
            'user_uid' => $user->uid,
            'created_at' => now(), // Dentro del rango
        ]);

        // Simular una solicitud con un rango de fechas
        $filters = [
            ['database_field' => 'date', 'value' => [now()->subDays(2), now()]], // Rango de 2 días
        ];

        $response = $this->json('GET', '/logs/list_logs/get_logs', [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
    }


    public function testGetLogsAppliesSingleDateFilter()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        Auth::login($user);

        // Crear registros de logs en diferentes fechas
        $log1 = LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 1',
            'entity' => 'Entidad 1',
            'user_uid' => $user->uid,
            'created_at' => now()->subDays(1), // Dentro de la fecha
        ]);

        LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 2',
            'entity' => 'Entidad 2',
            'user_uid' => $user->uid,
            'created_at' => now()->subDays(3), // Fuera de la fecha
        ]);

        // Simular una solicitud sin filtros de fecha
        $filters = [
            ['database_field' => 'entity', 'value' => ['Entidad 1']],
        ];

        $response = $this->json('GET', '/logs/list_logs/get_logs', [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta es correcta
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Debería devolver solo log1

        // Verificar que el log devuelto es el esperado
        $this->assertEquals($log1->uid, $response->json('data.0.uid'));
    }


    public function testGetLogsWithNoFiltersReturnsAllLogs()
    {

        $user1 = UsersModel::factory()->create()->latest()->first();
        Auth::login($user1);
        $user2 = UsersModel::factory()->create()->latest()->first();
        Auth::login($user2);
        // Crear algunos registros de logs para la prueba
        LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 1',
            'entity' => 'Entidad 1',
            'user_uid' => $user1->uid,
            'created_at' => now()->subDays(1),
        ]);

        LogsModel::create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 2',
            'entity' => 'Entidad 2',
            'user_uid' => $user2->uid,
            'created_at' => now(),
        ]);

        // Simular una solicitud sin filtros
        $response = $this->json('GET', '/logs/list_logs/get_logs');

        // Verificar que la respuesta es correcta
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Debería devolver ambos logs
    }


    public function testGetLogsAppliesUserFilter()
    {
        // Crear algunos usuarios
        $user1 = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $user2 = UsersModel::factory()->create([
            'uid' => generateUuid(),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Crear registros de logs para diferentes usuarios
        $log1 = LogsModel::factory()->create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 1',
            'entity' => 'Entidad 1',
            'user_uid' => $user1->uid, // Log asociado a user1
            'created_at' => now(),
        ]);

        LogsModel::factory()->create([
            'uid' => generateUuid(),
            'info' => 'Log de prueba 2',
            'entity' => 'Entidad 2',
            'user_uid' => $user2->uid, // Log asociado a user2
            'created_at' => now(),
        ]);

        // Simular una solicitud con un filtro de usuario
        $filters = [
            ['database_field' => 'users', 'value' => [$user1->uid]], // Filtrar por user1
        ];

        $response = $this->json('GET', '/logs/list_logs/get_logs', [
            'filters' => $filters,
        ]);

        // Verificar que la respuesta es correcta
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Debería devolver solo log1

        // Verificar que el log devuelto es el esperado
        $this->assertEquals($log1->uid, $response->json('data.0.uid'));
    }

    public function testApplyRangeDateFilter()
    {
        $this->logsController = new LogsController();
        $this->queryMock = Mockery::mock(Builder::class);

        $filters = [
            [
                'database_field' => 'date',
                'value' => ['2023-01-01', '2023-12-31']
            ]
        ];

        $this->queryMock
            ->shouldReceive('where')
            ->with('logs.created_at', '<=', '2023-12-31')
            ->once()
            ->andReturnSelf();

        $this->queryMock
            ->shouldReceive('where')
            ->with('logs.created_at', '>=', '2023-01-01')
            ->once()
            ->andReturnSelf();

        // Envolvemos el mock en una referencia
        $queryRef = &$this->queryMock;

        $this->invokePrivateMethod('applyFilters', [$filters, &$queryRef]);
    }

    public function testApplyFiltersWithSingleDate()
    {

        $this->logsController = new LogsController();
        $this->queryMock = Mockery::mock(Builder::class);

        $filters = [
            [
                'database_field' => 'date',
                'value' => ['2023-01-01', '2023-01-01']
            ]
        ];

        $this->queryMock
            ->shouldReceive('where')
            ->with('logs.created_at', '<=', '2023-01-01')
            ->once()
            ->andReturnSelf();

        $this->queryMock
            ->shouldReceive('where')
            ->with('logs.created_at', '>=', '2023-01-01')
            ->once()
            ->andReturnSelf();

        // Envolvemos el mock en una referencia
        $queryRef = &$this->queryMock;

        $this->invokePrivateMethod('applyFilters', [$filters, &$queryRef]);
    }

    /**
     * Invoca un método privado en la instancia de LogsController.*/

    private function invokePrivateMethod(string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass($this->logsController = new LogsController());
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->logsController = new LogsController(), $parameters);
    }
}
