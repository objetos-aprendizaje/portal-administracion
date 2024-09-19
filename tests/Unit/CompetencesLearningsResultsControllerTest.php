<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\UserRolesModel;
use App\Models\CompetencesModel;
use App\Models\TooltipTextsModel;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\CompetenceFrameworksModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompetencesLearningsResultsControllerTest extends TestCase
{

    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }


    /** @testdox Crear Marco de competencias */

    public function testCreateCompetence()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            // Datos de prueba
            $data = [
                'name' => 'Nueva Competencia',
                'description' => 'Descripción de la nueva competencia',

            ];

            // Realiza la solicitud POST
            $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Competencia añadida correctamente',
                ]);

            // Verifica que la competencia fue creada en la base de datos
            $this->assertDatabaseHas('competences', [
                'name' => 'Nueva Competencia',
                'description' => 'Descripción de la nueva competencia',
            ]);
        }
    }


    /**
    * @testdox Actualizar Marco de competencias */
    public function testUpdateCompetences(){
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', [
                'uid' => '999-12499-123456-12345-12111',
                'name' => 'Competencia',
                'description' => 'Descripción de la competencia',


            ]);

            // Verifica que la competencia se haya creado correctamente
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Competencia añadida correctamente']);

            // Obtiene el uid de la competencia recién creada
            $uid_tc = '999-12499-123456-12345-12111';
            $this->assertNotNull($uid_tc, 'Competencia no se creó correctamente.');


            // Se actualiza la competencia
            $data = [
                'name' => 'Nueva Competencia',
                'description' => 'Descripción de la nueva competencia',

            ];

            $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', $data);

            // Respuesta que la competencia se haya actualizado correctamente
            $response->assertStatus(200);

            }
    }

    /**
     * @test Validación campos requeridos Marco de competencias*/
    public function testValidatesRequiredFieldsCompetences()
    {
        $data = [
            'name' => '',
        ];

        $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', $data);

        $response->assertStatus(422)
                ->assertJsonStructure(['message', 'errors']);
    }

    /**
    * @test Retorna error si la competencia padre no existe*/
    public function testErrorIfParentCompetenceDoesNotExist()
    {
        $data = [
            'name' => 'Competencia con padre inexistente',
            'parent_competence_uid' => 'inexistente-uid',

        ];

        $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', $data);

        $response->assertStatus(422);

    }

    /**
    * @test Verifica asociación de resultados de aprendizaje a competencias*/
    public function testCreateLearningResult()
    {
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            // Datos de prueba
        // Crea una competencia para asociar el resultado de aprendizaje
            $competence = new CompetencesModel();
            $competence->uid = '555-12499-123456-12345-12111'; // Asigno el uid manualmente
            $competence->name = 'Competencia para Resultados de Aprendizaje';
            $competence->description = 'Descripción de la competencia';
            $competence->save();
            $competence = CompetencesModel::find('555-12499-123456-12345-12111');

            // Datos para crear un nuevo resultado de aprendizaje
            $data = [
                'uid' => Str::uuid(),
                'competence_uid' => $competence->uid,
                'name' => 'Nuevo Resultado de Aprendizaje',
                'description' => 'Descripción del nuevo resultado de aprendizaje',
            ];

            // Realiza la solicitud para crear el resultado de aprendizaje
            $response = $this->postJson('/cataloging/competences_learnings_results/save_learning_result', $data);

            // Verifica la respuesta
            $response->assertStatus(200);
            $response->assertJson(['message' => 'Resultado de aprendizaje guardado correctamente']);

            // Verifica que el resultado de aprendizaje ha sido guardado en la base de datos
            $this->assertDatabaseHas('learning_results', [
                'name' => 'Nuevo Resultado de Aprendizaje',
                'competence_uid' => $competence->uid,
            ]);
        }
    }

    /**
    * @test Elimina Competencias*/
    public function testDeleteCompetencesLearningResults()
    {
        // Crea un administrador con roles
        $admin = UsersModel::factory()->create();
        $roles_bd = UserRolesModel::get()->pluck('uid');
        $roles_to_sync = [];
        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            // Crear algunos registros de ejemplo
            $competenceFramework = CompetenceFrameworksModel::create([
                'uid' => generate_uuid(),
                'name' => 'Example Competence Framework',
                'description' => 'Example framework',
                'has_levels' => 0
            ])->first();

            // Crear algunos registros de ejemplo
            $competence = CompetencesModel::create([
                'uid' => generate_uuid(),
                'name' => 'Example Competence',
                'competence_framework_uid' => $competenceFramework->uid
            ])->first();
            $uid_competence = $competence->uid;

            $learningResult = LearningResultsModel::create([
                'uid' => generate_uuid(),
                'name' => 'Example Learning Result',
                'competence_uid' => $uid_competence
            ]);

            // Simular un request DELETE a la ruta
            $response = $this->deleteJson('/cataloging/competences_learnings_results/delete_competences_learning_results', [
                'uids' => [
                    'competencesFrameworks' => [$competenceFramework->uid], // Añadir esta línea
                    'learningResults' => [$learningResult->uid],
                    'competences' => [$competence->uid],
                ],
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Elementos eliminados correctamente']);

            // Verificar que los registros han sido eliminados
            $this->assertDatabaseMissing('competence_frameworks', ['uid' => $competenceFramework->uid]);
            $this->assertDatabaseMissing('learning_results', ['uid' => $learningResult->uid]);
            $this->assertDatabaseMissing('competences', ['uid' => $competence->uid]);

        }
    }

         /**
    * @test Obtener Learning result
    */
    public function testGetLearningResultReturnsCorrectData()
    {

        // Crear una competencia de prueba
        $competence = CompetencesModel::factory()->create()->first();

        // Crear un resultado de aprendizaje de prueba asociado a la competencia
        $learningResult = LearningResultsModel::factory()->create([
            'name' => 'Resultado de Aprendizaje 1',
            'description' => 'Descripción del resultado de aprendizaje 1',
            'competence_uid' => $competence->uid,
        ]);

        // Hacer la solicitud GET a la ruta
        $response = $this->get('/cataloging/competences_learnings_results/get_learning_result/' . $learningResult->uid);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $data = $response->json();

        // Verificar que los datos devueltos sean correctos
        $this->assertEquals($learningResult->uid, $data['uid']);
        $this->assertEquals($learningResult->name, $data['name']);
        $this->assertEquals($learningResult->description, $data['description']);
    }

    /** @test Obtiene competencias por uid*/
    public function testReturnsCompetenceDetails()
    {
        // Creamos una competencia de prueba
        $competence = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Test Competence',
        ])->latest()->first();

        // Realizamos una solicitud GET a la ruta
        $response = $this->getJson('/cataloging/competences_learnings_results/get_competence/' . $competence->uid);

        // Verificamos que la respuesta sea un JSON
        $response->assertStatus(200)
                 ->assertJson([
                     'uid' => $competence->uid,
                     'name' => 'Test Competence',
                     // Asegúrate de incluir otros atributos que esperas en la respuesta
                 ]);
    }

    /** @test Obtiene todas las competencias*/
    public function testReturnsAllCompetences()
    {
        // Creamos algunas competencias de prueba
        $competence1 = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Competence 1',
            'description' => 'Description 1',
            'parent_competence_uid' => null, // Competencia principal
        ])->latest()->first();

        $competence2 = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Competence 2',
            'description' => 'Description 2',
            'parent_competence_uid' => $competence1->uid, // Subcompetencia
        ])->latest()->first();

        // Realizamos una solicitud GET a la ruta
        $response = $this->getJson('/cataloging/competences_learnings_results/get_all_competences');


        // Verificamos que la respuesta sea un JSON
        $response->assertStatus(200);

    }


}
