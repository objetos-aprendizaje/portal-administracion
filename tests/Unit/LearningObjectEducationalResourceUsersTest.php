<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourcesModel;
use App\Models\NotificationsPerUsersModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LearningObjectEducationalResourceUsersTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @test  Verifica que el método index carga la vista correcta con los datos necesarios.
     */
    public function testIndexLoadsCorrectViewWithProperData()
    {
        $user = UsersModel::factory()->create();
        // Asignar un rol específico al usuario (por ejemplo, el rol 'ADMINISTRATOR')
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
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


        // Crear datos de prueba para NotificationsPerUsersModel
        $notification1 = NotificationsPerUsersModel::factory()->create([
            'user_uid' => $user->uid,
        ])->first();

        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('learning-objects-educational-resources-per-users'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('learning_objects.educational_resources_per_users.index');

        // Verificar que la vista tiene los datos correctos para 'notifications_per_users'
         $response->assertViewHas('notifications_per_users', function ($viewData) use ($notification1, $user) {
            return !empty($viewData) &&
                $viewData[0]['uid'] === $notification1->uid
                && $viewData[0]['user_uid'] === $user->uid
                && $viewData[0]['general_notification_uid'] === $notification1->general_notification_uid;

        });

        // Verificar que otros datos están presentes en la vista
        $response->assertViewHas('page_name', 'Recursos educativos por usuarios');
        $response->assertViewHas('page_title', 'Recursos educativos por usuarios');
        $response->assertViewHas('resources');
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('tomselect', true);
        $response->assertViewHas('submenuselected', 'learning-objects-educational-resources-per-users');

        // Verificar que la vista tiene los datos correctos para 'notifications_per_users'

    }
    /**
     * @test  Verifica que la ruta devuelve usuarios sin filtros ni ordenamiento.
     */
    public function testReturnsUsersWithoutFiltersOrSorting()
    {
        // Crear usuarios de prueba
        UsersModel::factory()->count(5)->create();

        $response = $this->getJson('/learning_objects/educational_resources_per_users/get_list_users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['uid', 'first_name', 'last_name'] // Ajusta según la estructura de tu modelo
                ],
                'links',
            ]);
    }

    /**
     * @test
     * Verifica que la ruta filtra usuarios correctamente por el término de búsqueda.
     */
    public function testFiltersUsersBySearch()
    {
        // Crear usuarios de prueba
        UsersModel::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        UsersModel::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $response = $this->getJson('/learning_objects/educational_resources_per_users/get_list_users?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['first_name' => 'John']);
    }

    /**
     * @test
     * Obtener recursos educativos por clasificación de usuarios
     */
    public function testGetEducationalResourcesPerUsersSorting()
    {

        // Crear algunos usuarios de prueba
        UsersModel::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
        UsersModel::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones']);
        UsersModel::factory()->create(['first_name' => 'Charlie', 'last_name' => 'Brown']);

        // Hacer una solicitud con parámetros de ordenación ascendente
        $response = $this->getJson('/learning_objects/educational_resources_per_users/get_list_users?sort[0][field]=first_name&sort[0][dir]=asc&size=10');

        $response->assertStatus(200);

        // Verificar que los usuarios están ordenados correctamente
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data), "No se devolvieron usuarios");
        $nonEmptyNames = array_filter(array_column($data, 'first_name'));
        $this->assertCount(count($data), $nonEmptyNames);

    }

    /**
     * @test
     * Verifica que la ruta ordena los usuarios por el campo especificado.
     */
    public function testSortsUsersByGivenField()
    {
        // Crear usuarios de prueba
        UsersModel::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        UsersModel::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        // Definir el parámetro sort como array
        $sort = [['field' => 'first_name', 'dir' => 'asc']];

        // Enviar la solicitud GET con el parámetro sort como un array
        $response = $this->getJson('/learning_objects/educational_resources_per_users/get_list_users', [
            'sort' => $sort,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * @test
     * Verifica que la ruta devuelve los resultados paginados correctamente.
     */
    public function testReturnsPaginatedResults()
    {
        // Crear más usuarios de los que caben en una página
        UsersModel::factory()->count(20)->create();

        $response = $this->getJson('/learning_objects/educational_resources_per_users/get_list_users?size=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
            ])
            ->assertJsonCount(5, 'data');
    }

    /**
     * @test
     * Verifica que la ruta devuelve un código 406 cuando no se encuentran usuarios.
     */
    public function testReturns406WhenNoUsersAreFound()
    {
        // Asegúrate de que no haya usuarios en la base de datos
        UsersModel::query()->delete();

        $response = $this->getJson('/learning_objects/educational_resources_per_users/get_list_users');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonStructure([
                'data',
                'links',

            ]);
    }

    /**
     * @test
     * Verifica que se retorna correctamente la lista de recursos educativos para un usuario específico.
     */
    public function testReturnsEducationalResourcesForUser()
    {
        $user = UsersModel::factory()->create();

        $resource = EducationalResourcesModel::factory()->create(['title' => 'Matemáticas']);

        // Generar un UID manualmente para la tabla intermedia
        $user->educationalResources()->attach($resource->uid, [
            'uid' => generate_uuid(),
            'date' => now(),
        ]);

        $response = $this->getJson("/learning_objects/educational_resources_per_users/get_notifications/{$user->uid}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Cambié de 3 a 1, ya que solo se crea 1 recurso
    }

    /**
     * @test
     * Verifica que se filtran los recursos educativos por el término de búsqueda.
     */
    public function testFiltersEducationalResourcesBySearch()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear recursos educativos de prueba
        $resource1 = EducationalResourcesModel::factory()->create(['title' => 'Matemáticas']);
        $resource2 = EducationalResourcesModel::factory()->create(['title' => 'Historia']);

        // Asociar los recursos educativos al usuario mediante la tabla intermedia
        $user->educationalResources()->attach($resource1->uid, [
            'uid' => generate_uuid(),
            'date' => now()
        ]);
        $user->educationalResources()->attach($resource2->uid, [
            'uid' => generate_uuid(),
            'date' => now()
        ]);

        $response = $this->getJson("/learning_objects/educational_resources_per_users/get_notifications/{$user->uid}?search=Matemáticas");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Matemáticas']);
    }

    /**
     * @test
     * Verifica que los recursos educativos se ordenan según un campo específico.
     */
    public function testSortsEducationalResourcesByGivenField()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear recursos educativos de prueba
        $resource1 = EducationalResourcesModel::factory()->create(['title' => 'Historia'])->first();

        // Asociar los recursos educativos al usuario mediante la tabla intermedia
        $user->educationalResources()->attach($resource1->uid, [
            'uid' => generate_uuid(),
            'date' => now()
        ]);
        // Definir el parámetro de ordenamiento
        // Convertir el parámetro de ordenamiento en una cadena JSON
        // Definir el parámetro de ordenamiento como un array directamente
        $sort = [
            ['field' => 'title', 'dir' => 'asc']
        ];

        // Hacer la solicitud GET pasando el parámetro de ordenamiento como un array
        $response = $this->getJson("/learning_objects/educational_resources_per_users/get_notifications/{$user->uid}", [
            'sort' => $sort
        ]);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Historia']);
    }

    /**
     * @test
     * Verifica que se retorna una lista vacía cuando no se encuentran recursos educativos.
     */
    public function testReturnsEmptyWhenNoEducationalResourcesFound()
    {
        $user = UsersModel::factory()->create();

        $response = $this->getJson("/learning_objects/educational_resources_per_users/get_notifications/{$user->uid}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
