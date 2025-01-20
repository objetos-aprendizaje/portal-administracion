<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EducationalResourceTypesControllerTest extends TestCase
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
     * @test
     * Verifica que el método index() del EducationalResourceTypesController
     * redirige a la vista de acceso denegado si el usuario no tiene acceso.
     */
    public function testRedirectsToAccessNotAllowedWhenAccessIsDeniedERT()
    {
        // Crear un usuario y asignarle el rol 'MANAGEMENT'
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generateUuid()]
        ]);
        Auth::login($user);

        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Simular la configuración de general_options
        app()->instance('general_options', [
            'managers_can_manage_educational_resources_types' => false,
        ]);

        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('cataloging-educational-resources'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la de acceso denegado
        $response->assertViewIs('access_not_allowed');

        // Verificar que la vista tiene los datos correctos
        $response->assertViewHas('title', 'No tienes permiso para administrar los tipos de recurso educativo');
        $response->assertViewHas('description', 'El administrador ha bloqueado la administración de tipos de recurso educativo a los gestores.');
    }

    /**
     * @test
     * Verifica que el método index() del EducationalResourceTypesController
     * retorna la vista correcta con los datos necesarios si el usuario tiene acceso.
     */
    public function testLoadsTheEducationalResourcesTypesViewWithProperDataWhenAccessIsAllowedERT()
    {
        // Crear un usuario y asignarle el rol 'MANAGEMENT'
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generateUuid()]
        ]);
        Auth::login($user);

        // Simular la configuración de general_options
        app()->instance('general_options', [
            'managers_can_manage_educational_resources_types' => true,
        ]);

        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Crear tipos de recursos educativos de prueba
        $resourceType1 = EducationalResourceTypesModel::factory()->create();
        $resourceType2 = EducationalResourceTypesModel::factory()->create();

        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('cataloging-educational-resources'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('cataloging.educational_resource_types.index');

        // Verificar que la vista tiene los datos correctos para 'educational_resource_types'
        $response->assertViewHas('educational_resource_types', function ($viewData) use ($resourceType1, $resourceType2) {
            return in_array($resourceType1->toArray(), $viewData) &&
                in_array($resourceType2->toArray(), $viewData);
        });

        // Verificar que otros datos están presentes en la vista
        $response->assertViewHas('page_name', 'Tipos de recursos educativos');
        $response->assertViewHas('page_title', 'Tipos de recursos educativos');
        $response->assertViewHas('submenuselected', 'cataloging-educational-resources');
    }

    /**
     * @test Obtener todos los recursos educativos
     */

    public function testGetEducationalResourceTypesReturnsJson()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        // Crear algunos registros de tipo de recurso educativo
        EducationalResourceTypesModel::factory()->count(5)->create();

        // Realizar la solicitud a la ruta
        $response = $this->get('/cataloging/educational_resources_types/get_list_educational_resource_types?search=Jhon&sort[0][field]=name&sort[0][dir]=asc');

        // Verificar que la respuesta sea un JSON
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
            'total',
        ]);
    }

    /**
     * @test Obtener un uid de los recursos educativos
     */
    public function testGetEducationalResourceUid()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear un registro de tipo de recurso educativo
        $educationalresourse = EducationalResourceTypesModel::factory()->create()->first();
        $this->assertDatabaseHas('educational_resource_types', ['uid' => $educationalresourse->uid]);

        $data = [
            'uid' => $educationalresourse->uid,
        ];


        // Realizar la solicitud a la ruta con el UID
        $response = $this->get('/cataloging/educational_resources_types/get_educational_resource_type/' . $data['uid']);

        // Verificar que la respuesta sea un JSON y contenga los datos correctos
        $response->assertStatus(200);
        $response->assertJsonFragment(['uid' => $educationalresourse->uid]);


        // lanzando el error 406
        // Realizar la solicitud a la ruta con el UID
        $response = $this->get('/cataloging/educational_resources_types/get_educational_resource_type/' . generateUuid());

        // Verificar que la respuesta sea un JSON y contenga los datos correctos
        $response->assertStatus(406)
            ->assertJson([
                'message' => 'El tipo de recurso educativo no existe',
            ]);
    }


    /**
     * @testdox Crear Recursos exitoso*/
    public function testCreateResources()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            // Datos de prueba
            $data = [
                'name' => 'Recurso de Prueba',
                'description' => 'Descripción del recurso',
            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/cataloging/educational_resources_types/save_educational_resource_type', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Tipo de recurso educativo añadido correctamente',
                ]);

            // Verifica que el recurso fue creado en la base de datos
            $this->assertDatabaseHas('educational_resource_types', [
                'name' => 'Recurso de Prueba',
                'description' => 'Descripción del recurso',
            ]);
        }
    }

    /**
     * @test Validación de campos requeridos en recurso educativo*/
    public function testValidatesRequiredfields()
    {
        // Datos de prueba incompletos
        $data = [
            'name' => '', // Campo requerido
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/cataloging/educational_resources_types/save_educational_resource_type', $data);

        // Verificar la respuesta
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /**
     * @test  Actualiza recurso Educativo*/
    public function testUpdatesEducationalResourceType()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {


            $educationResourseType = EducationalResourceTypesModel::factory()->create();

            $data = [
                'educational_resource_type_uid' => $educationResourseType->uid,
                'name' => 'Tipo de curso actualizado',
                'description' => 'Descripción actualizada del tipo de curso',
            ];

            $response = $this->postJson('/cataloging/educational_resources_types/save_educational_resource_type', $data);

            // Verifica queel recurso se haya creado correctamente
            $response->assertStatus(200)
                ->assertJson(['message' => 'Tipo de recurso educativo actualizado correctamente']);
        }
    }

    /**
     * @testdox Elimina recurso educativo */
    public function testDeleteResource()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            $uids = [];

            $educationalResourceTypes = EducationalResourceTypesModel::factory()->count(5)->create();

            foreach ($educationalResourceTypes as $educationalResourceType) {
                $uids[] = [
                    $educationalResourceType->uid
                ];
            }

            // Realiza la solicitud DELETE
            $responseDelete = $this->deleteJson('/cataloging/educational_resources_types/delete_educational_resource_types', [
                'uids' => $uids,
            ]);

            $responseDelete->assertStatus(200);
            $responseDelete->assertJson(['message' => 'Tipos de recurso educativo eliminados correctamente']);
        }
    }

    /**
     * @testdox Elimina recurso educativo */
    public function testDeleteEducationalResourceTypesWith406()
    {

        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generateUuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }
        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        $educationalResouceType = EducationalResourceTypesModel::factory()->create()->first();

        EducationalResourcesModel::factory()->withStatus()->withCreatorUser()->create(
            [
                'educational_resource_type_uid' => $educationalResouceType->uid
            ]
        );

        // Realiza la solicitud DELETE
        $responseDelete = $this->deleteJson('/cataloging/educational_resources_types/delete_educational_resource_types', [
            'uids' => [$educationalResouceType->uid],
        ]);

        $responseDelete->assertStatus(406);
        $responseDelete->assertJson(['message' => 'No se pueden eliminar los tipos seleccionados porque están siendo utilizados por recursos educativos']);
    }
}
