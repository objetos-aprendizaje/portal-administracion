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
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Http\Controllers\Administration\PaymentsController;

class AdministrationGeneralOptionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
        public function setUp(): void {
            parent::setUp();
            $this->withoutMiddleware();
            // Asegúrate de que la tabla 'qvkei_settings' existe
            $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
        }



/**
*  @test  Guardar Lanes Show */
    public function testSaveLanesShow()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Datos de prueba
        $data = [
            'lane_featured_courses' => true,
            'lane_featured_educationals_programs' => false,
            'lane_recents_educational_resources' => true,
            'lane_featured_itineraries' => false,
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/lanes_show/save_lanes_show', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
                ->assertJson(['message' => 'Preferencias de carriles actualizados correctamente']);

        // Verificar que los datos se hayan guardado en la base de datos
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_featured_courses',
            'option_value' => true,
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_featured_educationals_programs',
            'option_value' => false,
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_recents_educational_resources',
            'option_value' => true,
        ]);

        $this->assertDatabaseHas('general_options', [
            'option_name' => 'lane_featured_itineraries',
            'option_value' => false,
        ]);
    }


/** Group LMS Systems*/
/**
 * @test  Guardar Lms Systems */
    public function testSaveLmsSystemCreatesNewLms()
    {
        // Crear un usuario y autenticar
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $lms = LmsSystemsModel::factory()->create()->first();
        $this->assertDatabaseHas('lms_systems', ['uid' => $lms->uid]);


        // Enviar una solicitud para crear un nuevo sistema LMS
        $data = [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ];

        $response = $this->postJson('/administration/lms_systems/save_lms_system', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                ->assertJson(['message' => 'LMS añadido correctamente']);

        // Verificar que el sistema LMS se haya creado en la base de datos
        $this->assertDatabaseHas('lms_systems', [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ]);
    }

/**
*  @test  Actualiza LMS  */
    public function testSaveLmsSystemUpdatesExistingLms()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $lms = LmsSystemsModel::factory()->create()->first();
        $this->assertDatabaseHas('lms_systems', ['uid' => $lms->uid]);
        // Enviar una solicitud para crear un nuevo sistema LMS
        $data = [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ];

        // Enviar una solicitud para actualizar el sistema LMS existente
        $response = $this->postJson('/administration/lms_systems/save_lms_system', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);

        // Verificar que el sistema LMS se haya actualizado en la base de datos
        $this->assertDatabaseHas('lms_systems', [
            'uid' => $lms->uid,
            'name' => $lms->name,
            'identifier' => $lms->identifier,
        ]);
    }

/**
*  @test  Guardar LMS campos obligatorio error */
    public function testSaveLmsSystemValidationFails()
    {
        // Crear un usuario y autenticar
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Enviar una solicitud con datos inválidos (sin nombre ni identificador)
        $response = $this->postJson('/administration/lms_systems/save_lms_system', []);

        // Verificar que la respuesta sea un error de validación
        $response->assertStatus(422)
                    ->assertJsonStructure(['message', 'errors']);
    }

/**
*  @test  Elimina LMS  */

    public function testDeleteLmsSystems()
        {
            // Crear un usuario y autenticar
            $user = UsersModel::factory()->create();
            $this->actingAs($user);

            // Crear algunos sistemas LMS para eliminar
            $lms1 = LmsSystemsModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Sistema LMS 1',
                'identifier' => 'lms-1',
            ]);

            $lms2 = LmsSystemsModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Sistema LMS 2',
                'identifier' => 'lms-2',
            ]);

            // Enviar una solicitud para eliminar los sistemas LMS
            $response = $this->deleteJson('/administration/lms_systems/delete_lms_systems', [
                'uids' => [$lms1->uid, $lms2->uid], // Enviar solo los UIDs
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                     ->assertJson(['message' => 'LMS eliminados correctamente']);

            // Verificar que los sistemas LMS se hayan eliminado de la base de datos
            $this->assertDatabaseMissing('lms_systems', [
                'uid' => $lms1->uid,
            ]);
            $this->assertDatabaseMissing('lms_systems', [
                'uid' => $lms2->uid,
            ]);

    }


/** @test  Obtener LMS por uid*/
    public function testGetLmsSystemByUid()
    {
        // Crear un sistema LMS en la base de datos
        $lmsSystem = LmsSystemsModel::factory()->create()->first();

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/lms_systems/get_lms_system/' . $lmsSystem->uid);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene los datos esperados
        $data = $response->json();
        $this->assertEquals($lmsSystem->uid, $data['uid']);
        $this->assertEquals($lmsSystem->name, $data['name']);
        $this->assertEquals($lmsSystem->identifier, $data['identifier']);
    }

/** @test  Lista LMS conp parámetros por defecto*/
     public function testListLmsSystemsWithDefaultParameters()
     {

        LmsSystemsModel::factory()->count(3)->create();


         $response = $this->get('/administration/lms_systems/get_lms_systems');

         $response->assertStatus(200);
         $response->assertJsonStructure([
             'current_page',
             'data' => [
                 '*' => [
                     'uid',
                     'name',
                 ],
             ],
             'last_page',
             'per_page',
             'total',
         ]);
     }

/** @test */
    public function testFilterLmsSystemsBySearch()
    {

        LmsSystemsModel::factory()->create(['name' => 'LMS System 1']);
        LmsSystemsModel::factory()->create(['name' => 'Another System']);
        $response = $this->get('/administration/lms_systems/get_lms_systems?search=LMS');

        $response->assertStatus(200);

    }

/** @test Ordenar lista LMS*/
    public function testSortLmsSystems()
    {
        LmsSystemsModel::factory()->create(['name' => 'LMS System 2']);
        LmsSystemsModel::factory()->create(['name' => 'Another System']);


        $response = $this->get('/administration/lms_systems/get_lms_systems?sort[0][field]=name&sort[0][dir]=asc&size=10');

        $response->assertStatus(200);

    }

/** @group Center Controller */

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

/**
 * test Obtener Centro por uid
*/
    public function testGetCenterByUid()
    {
        // Crear un centro de prueba
        $center = CentersModel::factory()->create()->first();

        // Hacer una petición GET a la ruta con el uid del centro
        $response = $this->get('/administration/centers/get_center/'.$center->uid);

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

/* @Group Center*/
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

}
