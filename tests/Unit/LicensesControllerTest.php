<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CentersModel;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\LmsSystemsModel;
use App\Models\LicenseTypesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourcesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Http\Controllers\Administration\LicensesController;

class LicensesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }
    /* @Group Licencia*/


    /** @test  Obtener Index View Licencia*/
    public function testIndexRouteReturnsViewLicenses()
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
        // Realiza una solicitud GET a la ruta
        $response = $this->get(route('licenses'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se retorne la vista correcta
        $response->assertViewIs('administration.licenses.index');

        // Verifica que los datos se pasen correctamente a la vista
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('page_name', 'Licencias');
        $response->assertViewHas('page_title', 'Licencias');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/licenses.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'licenses');
    }
    /**
     * @test Crear Licencias  */
    public function testCreateLicense()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        $data = [
            'uids' => [$license->uid],
            'name' => $license->name,
        ];

        $response = $this->postJson('/administration/licenses/save_license', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencia añadida correctamente']);

        $this->assertDatabaseHas('license_types', [
            'uid' => $license->uid,
            'name' => $license->name
        ]);
    }

    /**
     * @test Update Licencias  */
    public function testUpdateLicense()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create()->first();

        $data = [
            'license_uid' => $license->uid,
            'name' => $license->name,
        ];

        $response = $this->postJson('/administration/licenses/save_license', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencia actualizada correctamente']);
    }


    /** @test Error licencia invalida*/
    public function testSaveLicenseWithInvalidData()
    {
        $data = [
            'name' => '',
        ];

        $response = $this->postJson('/administration/licenses/save_license', $data);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Algunos campos son incorrectos'])
            ->assertJsonStructure(['errors' => ['name']]);
    }
    /** @test  Actualizar licencia*/
    public function testSaveLicenseUpdatesExistingLicense()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        $data = [
            'uid' => $license->uid,
            'name' => 'Licencia prueba'
        ];

        $response = $this->postJson('/administration/licenses/save_license', $data);


        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencia añadida correctamente']);

        $this->assertDatabaseHas('license_types', [
            'name' => 'Licencia prueba',
        ]);
    }

    /** @test Elimina Licencia*/
    public function testDeleteLicensesSuccessfully()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        $data = [
            'uids' => [$license->uid],
        ];


        // Realiza la solicitud DELETE para eliminar la licencia
        $response = $this->deleteJson('/administration/licenses/delete_licenses', $data);

        // Verifica que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Licencias eliminadas correctamente']);

        // Verifica que la licencia ya no existe en la base de datos
        $this->assertDatabaseMissing('license_types', [
            'uid' => $license->uid,
        ]);
    }

    /** @test Elimina Licencia Error 406*/
    public function testDeleteLicensesWith406()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        $license = LicenseTypesModel::factory()->create();

        EducationalResourcesModel::factory()->withStatus()->withEducationalResourceType()->create(
            [
                'creator_user_uid' => $user->uid,
                'license_type_uid' => $license->uid,
            ]
        );

        $data = [
            'uids' => [$license->uid],
        ];
        // Realiza la solicitud DELETE para eliminar la licencia
        $response = $this->deleteJson('/administration/licenses/delete_licenses', $data);

        // Verifica que la respuesta sea correcta
        $response->assertStatus(406)
            ->assertJson(['message' => 'No se pueden eliminar las licencias porque hay recursos educativos vinculados a ellos']);

    }


    /**
     * @test
     * Este test verifica que se puedan obtener las licencias con paginación,
     * búsqueda y ordenamiento aplicados correctamente.
     */
    public function testGetLicensesWithPaginationSearchAndSort()
    {
        // Crear licencias simuladas en la base de datos
        LicenseTypesModel::factory()->count(10)->create([
            'name' => 'Test License'
        ]);

        LicenseTypesModel::factory()->count(5)->create([
            'name' => 'Other License'
        ]);

        // Simular la solicitud con paginación, búsqueda y ordenamiento
        $request = Request::create('/administration/licenses/get_licenses', 'GET', [
            'size' => 5,
            'search' => 'Test',
            'sort' => [
                ['field' => 'name', 'dir' => 'asc']
            ]
        ]);

        // Instanciar el controlador
        $controller = new LicensesController();

        // Ejecutar el método del controlador
        $response = $controller->getLicenses($request);

        // Convertir la respuesta en una instancia de TestResponse para utilizar assertStatus y assertJson
        $response = $this->createTestResponse($response);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que el número de elementos en la respuesta sea el esperado
        $responseData = $response->json();
        $this->assertCount(5, $responseData['data']); // Verifica que hay 5 elementos en la respuesta paginada

        // Verificar que los elementos estén ordenados correctamente
        $this->assertEquals('Test License', $responseData['data'][0]['name']);
    }

    /**
     * @test
     * Este test verifica que se puede obtener una licencia específica
     * por su `uid` y que los datos devueltos son correctos.
     */
    public function testGetLicenseByUid()
    {
        // Crear una licencia simulada en la base de datos
        $license = LicenseTypesModel::factory()->create([
            'name' => 'Test License',
        ])->first();

        // Simular la solicitud GET con el `license_uid`
        $response = $this->get("/administration/licenses/get_license/{$license->uid}");

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que los datos devueltos sean correctos
        $response->assertJson([
            'uid' => $license->uid,
            'name' => 'Test License',
        ]);
    }
}
