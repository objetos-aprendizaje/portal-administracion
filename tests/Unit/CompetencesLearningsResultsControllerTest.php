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
use App\Models\CompetenceFrameworksLevelsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Cataloging;
use App\Http\Controllers\Cataloging\CompetencesLearningsResultsController;

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
    public function testUpdateCompetences()
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


            $competencia = CompetencesModel::factory()->create();

            $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', [
                'competence_uid' => $competencia->uid,
                'name' => 'Competencia',
                'description' => 'Descripción de la competencia',
            ]);

            // Verifica que la competencia se haya creado correctamente
            $response->assertStatus(200)
                ->assertJson(['message' => 'Competencia modificada correctamente']);
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
            'parent_competence_uid' => generate_uuid(),

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
            $competence->uid = generate_uuid(); // Asigno el uid manualmente
            $competence->name = 'Competencia para Resultados de Aprendizaje';
            $competence->description = 'Descripción de la competencia';
            $competence->save();
            $competence = CompetencesModel::find($competence->uid);

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
     * @test Verifica asociación de resultados de aprendizaje a competencias*/
    public function testCreateLearningResultWithError422()
    {

        $admin = UsersModel::factory()->create();

        $rol = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $admin->roles()->attach($rol->uid, ['uid' => generate_uuid()]);

        $this->actingAs($admin);

        // Datos para crear un nuevo resultado de aprendizaje
        $data = [
            // 'name' => 'Nuevo Resultado de Aprendizaje',
            'description' => 'Descripción del nuevo resultado de aprendizaje',
        ];

        // Realiza la solicitud para crear el resultado de aprendizaje
        $response = $this->postJson('/cataloging/competences_learnings_results/save_learning_result', $data);

        // Verifica la respuesta
        $response->assertStatus(422);
        $response->assertJson(['message' => 'El nombre es obligatorio.']);
        // $response->assertJson(['message' => 'Algunos campos son incorrectos']);

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
                'description' => 'Example Competence',
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

    /**
     * @test
     * Prueba que se pueden buscar resultados de aprendizaje correctamente.
     */
    public function testSearchLearningResults()
    {
        // Crear algunos resultados de aprendizaje simulados
        $learningResult1 = LearningResultsModel::factory()->withCompetence()->create([
            'name' => 'Result 1',
        ]);
        $learningResult2 = LearningResultsModel::factory()->withCompetence()->create([
            'name' => 'Result 2',
        ]);

        // Hacer la solicitud GET a la ruta con un término de búsqueda
        $query = 'Result';
        $response = $this->getJson('/searcher/get_learning_results/' . $query);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que los resultados correctos se devuelven en la respuesta
        $response->assertJsonFragment(['name' => 'Result 1']);
        $response->assertJsonFragment(['name' => 'Result 2']);
    }

    /**
     * @test
     * Prueba que se puede obtener el marco de competencia correctamente.
     */
    public function testGetCompetenceFramework()
    {
        // Crear un marco de competencia simulado con niveles asociados
        $competenceFramework = CompetenceFrameworksModel::factory()->create()->first();

        CompetenceFrameworksLevelsModel::factory()->count(3)->create(
            [
                'competence_framework_uid' => $competenceFramework->uid,
            ]
        );

        // Hacer la solicitud GET a la ruta con el UID del marco de competencia
        $response = $this->getJson('/cataloging/competences_learnings_results/get_competence_framework/' . $competenceFramework->uid);

        // Verificar que la respuesta es exitosa
        $response->assertStatus(200);

        // Verificar que el marco de competencia y los niveles se devuelven correctamente
        $response->assertJsonFragment(['uid' => $competenceFramework->uid]);
        $this->assertCount(3, $competenceFramework->levels);
    }

    /**
     * @test Guarda y actualiza marcos de competencias con diferentes configuraciones de niveles
     */
    public function testSaveCompetenceFrameworkWithLevelsFunction()
    {
        $admin = UsersModel::factory()->create();

        $rol = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $admin->roles()->attach($rol->uid, ['uid' => generate_uuid()]);

        $this->actingAs($admin);

        // Crear un caso donde se guarde un nuevo marco de competencias con niveles
        $dataNew = [
            'name' => 'Competencia Nueva',
            'description' => 'Descripción de la competencia nueva',
            'has_levels' => '1',
            'levels' => json_encode([
                ['uid' => null, 'name' => 'Nivel 1'],
                ['uid' => null, 'name' => 'Nivel 2'],
            ])
        ];

        $response = $this->postJson('/cataloging/competences_learnings_results/save_competence_framework', $dataNew);

        // Verificar que la respuesta sea exitosa y que el mensaje sea correcto
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Marco de competencias añadido correctamente']);

        // Verificar que el marco de competencias y los niveles se hayan guardado en la base de datos
        $competenceFramework = CompetenceFrameworksModel::where('name', 'Competencia Nueva')->first();
        $this->assertNotNull($competenceFramework);

        $savedLevels = CompetenceFrameworksLevelsModel::where('competence_framework_uid', $competenceFramework->uid)->get();
        $this->assertCount(2, $savedLevels);

        // Caso de actualización: eliminar un nivel y actualizar el nombre de otro
        $dataUpdate = [
            'competence_framework_modal_uid' => $competenceFramework->uid,
            'name' => 'Competencia Actualizada',
            'description' => 'Descripción actualizada',
            'has_levels' => '1',
            'levels' => json_encode([
                ['uid' => $savedLevels[0]->uid, 'name' => 'Nivel 1 Actualizado'],
            ])
        ];

        $response = $this->postJson('/cataloging/competences_learnings_results/save_competence_framework', $dataUpdate);
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Marco de competencias modificado correctamente']);

        // Verificar que el nivel eliminado ya no esté en la base de datos
        $this->assertDatabaseMissing('competence_frameworks_levels', ['uid' => $savedLevels[1]->uid]);

        // Verificar que el nivel actualizado esté en la base de datos con el nuevo nombre
        $this->assertDatabaseHas('competence_frameworks_levels', [
            'uid' => $savedLevels[0]->uid,
            'name' => 'Nivel 1 Actualizado'
        ]);
    }

    /**
     * @test Validación fallida al guardar marco de competencias
     */
    public function testSaveCompetenceFrameworkValidationFails()
    {
        // Actuar como usuario
        $this->actingAs(UsersModel::factory()->create());

        // Datos de entrada inválidos (sin nombre, lo cual es obligatorio)
        $data = [
            'description' => 'Descripción de prueba'
            // Falta el campo 'name', por lo que debería fallar
        ];

        // Realizar la solicitud POST con datos inválidos
        $response = $this->postJson('/cataloging/competences_learnings_results/save_competence_framework', $data);

        // Comprobar que se recibe un estado 422 de validación fallida
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Algunos campos son incorrectos']);
        $response->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * @test Eliminar niveles si has_levels es 0 en un marco de competencias existente
     */
    public function testSaveCompetenceFrameworkDeleteLevelsWhenHasLevelsIsZero()
    {
        // Crear un usuario autenticado y marco de competencia con niveles existentes
        $this->actingAs(UsersModel::factory()->create());
        $competenceFramework = CompetenceFrameworksModel::factory()->create();
        CompetenceFrameworksLevelsModel::factory()->count(3)->create([
            'competence_framework_uid' => $competenceFramework->uid
        ]);

        // Comprobar que existen niveles en el marco de competencias antes de la prueba
        $this->assertDatabaseCount('competence_frameworks_levels', 3);

        // Datos de entrada para desactivar niveles
        $data = [
            'competence_framework_modal_uid' => $competenceFramework->uid,
            'name' => 'Nuevo nombre de prueba',
            'has_levels' => "0" // Esto debería desencadenar la eliminación de niveles
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/cataloging/competences_learnings_results/save_competence_framework', $data);

        // Comprobar que se recibe una respuesta exitosa
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Marco de competencias modificado correctamente']);

        // Verificar que los niveles hayan sido eliminados
        $this->assertDatabaseCount('competence_frameworks_levels', 0);
    }

    public function testUpdateLearningResult()
    {
        // Crear un usuario y simular autenticación
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $competence = CompetencesModel::factory()->create()->first();

        // Crear un resultado de aprendizaje existente
        $learningResult = LearningResultsModel::factory()->create([
            'uid' => generate_uuid(),
            'competence_uid' => $competence->uid,
            'name' => 'Existing Learning Result',
            'description' => 'Description of the existing learning result.',
        ]);

        // Datos para la actualización
        $data = [
            'competence_uid' => $competence->uid,
            'learning_result_uid' => $learningResult->uid, // Para actualizar el resultado existente
            'name' => 'Updated Learning Result',
            'description' => 'Updated description of the learning result.',
        ];

        // Simular una solicitud POST a la ruta
        $response = $this->postJson('/cataloging/competences_learnings_results/save_learning_result', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Resultado de aprendizaje guardado correctamente']);

        // Verificar que el resultado de aprendizaje se haya actualizado en la base de datos
        $this->assertDatabaseHas('learning_results', [
            'uid' => $learningResult->uid,
            'name' => 'Updated Learning Result',
            'description' => 'Updated description of the learning result.',
        ]);
    }

    public function testImportEscoFrameworkValidationFails()
    {
        // Envía una solicitud POST a la ruta correspondiente sin archivos
        $response = $this->postJson('/cataloging/competences_learnings_results/import_esco_framework', []);

        // Verifica que la respuesta tenga un código de estado 422
        $response->assertStatus(422);
    }
}
