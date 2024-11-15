<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CenterControllerTest extends TestCase
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
    /** @group Center Controller */
    /**
     *  @test  Obtener Index View Centros */
    public function testIndexRouteReturnsViewCenter()
    {

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

        // Realiza una solicitud GET a la ruta
        $response = $this->get(route('centres'));

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que se retorne la vista correcta
        $response->assertViewIs('administration.centers.index');

        // Verifica que los datos se pasen correctamente a la vista
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('page_name', 'Centros');
        $response->assertViewHas('page_title', 'Centros');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/centers.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'centres');
    }


    /**
     *  @test  Valida Crear Centros */
    public function testSaveCenterSuccess()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Datos de prueba válidos
        $data = [
            'name' => 'Test Center',
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/centers/save_center', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Centro añadido correctamente']);

        // Verificar que el centro se haya guardado en la base de datos
        $this->assertDatabaseHas('centers', [
            'name' => 'Test Center',
        ]);
    }

    /**
     *  @test  Valida Crear Centros */
    public function testUpdateCenterSuccess()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $center = CentersModel::factory()->create()->first();

        // Datos de prueba válidos
        $data = [
            'name' => 'Test Center',
            'center_uid' => $center->uid,
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/centers/save_center', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Centro actualizado correctamente']);

        // Verificar que el centro se haya guardado en la base de datos
        $this->assertDatabaseHas('centers', [
            'name' => 'Test Center',
        ]);
    }



    /** @test Elimina Centro*/
    public function testDeleteCentersSuccess()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear algunos centros de prueba
        $center1 = CentersModel::factory()->create()->first();
        $center2 = CentersModel::factory()->create()->first();


        // Asegúrate de que los centros existen antes de la eliminación
        $this->assertDatabaseHas('centers', ['uid' => $center1->uid]);
        $this->assertDatabaseHas('centers', ['uid' => $center2->uid]);

        // Enviar la solicitud DELETE para eliminar los centros
        $response = $this->deleteJson('/administration/centers/delete_centers', [
            'uids' => [$center1->uid, $center2->uid]
        ]);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Centros eliminados correctamente']);

        // Verificar que los centros ya no existen en la base de datos
        $this->assertDatabaseMissing('centers', ['uid' => $center1->uid]);
        $this->assertDatabaseMissing('centers', ['uid' => $center2->uid]);
    }

    /** @test Elimina Centro*/
    public function testDeleteCentersWithCourseInclude()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear algunos centros de prueba
        $center1 = CentersModel::factory()->create()->first();

        CoursesModel::factory()->withCourseStatus()->withCourseType()->create([
            'center_uid'=> $center1->uid
        ]);       
        // Asegúrate de que los centros existen antes de la eliminación
        $this->assertDatabaseHas('centers', ['uid' => $center1->uid]);
       

        // Enviar la solicitud DELETE para eliminar los centros
        $response = $this->deleteJson('/administration/centers/delete_centers', [
            'uids' => [$center1->uid]
        ]);

        // Verificar la respuesta
        $response->assertStatus(406)
            ->assertJson(['message' => 'No se pueden eliminar los centros porque hay cursos vinculados a ellos']);
    }

    /**
     * test Obtener Centro por uid
     */
    public function testGetCenterByUid()
    {
        // Crear un centro de prueba
        $center = CentersModel::factory()->create()->first();

        // Hacer una petición GET a la ruta con el uid del centro
        $response = $this->get('/administration/centers/get_center/' . $center->uid);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que la respuesta contenga los datos del centro
        $response->assertJson([
            'uid' => $center->uid,
            'name' => $center->name,
        ]);
    }

    /**
     * @test Filtrar centro
     */
    public function testFilterCentersBySearch()
    {
        // Crear un centro de prueba
        CentersModel::factory()->create(['name' => 'Centro 1']);
        CentersModel::factory()->create(['name' => 'Unidad 1']);

        $response = $this->get('/administration/centers/get_centers?search=Centro');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test  Ordenar centros*/
    public function testSortCenters()
    {

        CentersModel::factory()->create(['name' => 'Center2']);
        CentersModel::factory()->create(['name' => 'Another Center']);


        $response = $this->get('/administration/lms_systems/get_lms_systems?sort[0][field]=name&sort[0][dir]=asc&size=10');

        $response->assertStatus(200);
    }

    /** @test */
    public function testSortsCentersAsc()
    {
        // Crea algunos registros de ejemplo en la base de datos
        CentersModel::factory()->create(['name' => 'Center A', 'created_at' => '2024-01-01']);
        CentersModel::factory()->create(['name' => 'Center B', 'created_at' => '2024-01-02']);
        CentersModel::factory()->create(['name' => 'Center C', 'created_at' => '2024-01-03']);
        // Define la solicitud con parámetros de ordenamiento
        $response = $this->json('GET', '/administration/centers/get_centers', [
            'size' => 3,
            'sort' => [
                ['field' => 'name', 'dir' => 'asc'],
            ],
        ]);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que los resultados estén ordenados por nombre
        $data = $response->json()['data'];
        $this->assertCount(3, $data);

        $this->assertEquals('Center A', $data[0]['name']);
        $this->assertEquals('Center B', $data[1]['name']);
        $this->assertEquals('Center C', $data[2]['name']);
    }

    public function testSortsCentersDesc()
    {
        // Crea algunos registros de ejemplo en la base de datos
        CentersModel::factory()->create(['name' => 'Center A', 'created_at' => '2024-01-01']);
        CentersModel::factory()->create(['name' => 'Center B', 'created_at' => '2024-01-02']);
        CentersModel::factory()->create(['name' => 'Center C', 'created_at' => '2024-01-03']);
        // Prueba con orden descendente
        $response = $this->json('GET', '/administration/centers/get_centers', [
            'size' => 3,
            'sort' => [
                ['field' => 'name', 'dir' => 'desc']
            ],
        ]);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // 2Verifica que los resultados estén ordenados por nombre en orden descendente
        $data = $response->json()['data'];
        $this->assertCount(3, $data);
        $this->assertEquals('Center C', $data[0]['name']);
        $this->assertEquals('Center B', $data[1]['name']);
        $this->assertEquals('Center A', $data[2]['name']);
    }

    /** @test */
    public function testValidatesCenterError422()
    {
        // Envía una solicitud POST sin el campo 'name'
        $response = $this->json('POST', '/administration/centers/save_center', [
            'name' => '',
        ]);

        // Verifica que la respuesta tenga un estado 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Verifica que el mensaje de error sea el esperado
        $response->assertJson([
            'message' => 'Algunos campos son incorrectos',
            'errors' => [
                'name' => ['El nombre es obligatorio.'],
            ],
        ]);
    }
}
