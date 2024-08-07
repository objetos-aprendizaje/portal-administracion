<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\CourseTypesModel;
use PHPUnit\Framework\Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;


class CatalogingTest extends TestCase
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
     * @testdox Crear Categoría Exitoso*/
    public function testCreateCategory()
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
            $response = $this->postJson('/cataloging/categories/save_category', [
                'name' => 'Nueva Categoría',
                'description' => 'Descripción de la nueva categoría',
                'color' => '#FFFFFF',
                'image_path' => UploadedFile::fake()->image('category.jpg'),
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => 'Categoría añadida correctamente']);

            $this->assertDatabaseHas('categories', [
                'name' => 'Nueva Categoría',
            ]);
        }
    }

    /**
     * @testdox Crear Categoría sin imagen*/
    public function testCreateCategoryWithoutImage()
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
            $response = $this->postJson('/cataloging/categories/save_category', [
                'name' => 'Categoría Sin Imagen',
                'color' => '#FFFFFF',
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure(['message', 'errors' => ['image_path']]);
        }
    }

    /**
     * @testdox Crear Categoría con validacion de error*/
    public function testSaveCategoryValidationErrors()
    {
        $response = $this->postJson('/cataloging/categories/save_category', []);
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /**
     * @testdox Actualizar Categoría*/

    public function testUpdateCategory()
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
            $response = $this->postJson('/cataloging/categories/save_category', [
                'uid' => '999-12456-123456-12345-12111',
                'name' => 'Categoría nueva',
                'description' => 'Descripción de categoría',
                'color' => '#ffffff',
                'image_path' => UploadedFile::fake()->image('category1.jpg'),
            ]);

            // Verifica que la categoría se haya creado correctamente
            $response->assertStatus(200)
                ->assertJson(['message' => 'Categoría añadida correctamente']);

            // Obtiene el uid de la categoría recién creada
            $uid_cat = '999-12456-123456-12345-12111';
            $this->assertNotNull($uid_cat, 'La categoría no se creó correctamente.');


            // Ahora, actualiza la categoría
            $data = [
                'name' => 'Categoría nueva2',
                'description' => 'Descripción actualizada',
                'color' => '#000000',
                'image_path' => UploadedFile::fake()->image('updated_category.jpg'),
            ];

            $response = $this->postJson('/cataloging/categories/save_category', $data);

            // Verifica que la categoría se haya actualizado correctamente
            $response->assertStatus(200);
        }
    }


    /**
     * @testdox Eliminar Categoría */
    public function testDeleteCategory()
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

            $response = $this->postJson('/cataloging/categories/save_category', [
                'uid' => '24ce6bf8-4f8e-9999-a5e0-b0a4608c1236',
                'name' => 'Categoría',
                'description' => 'Descripción de categoría',
                'color' => '#ffffff',
                'image_path' => UploadedFile::fake()->image('category1.jpg'),
            ]);

            $response->assertStatus(200);
            $categoryId = '24ce6bf8-4f8e-9999-a5e0-b0a4608c1236';

            // Realiza la solicitud DELETE
            $responseDelete = $this->deleteJson('/cataloging/categories/delete_categories', [
                'uids' => [$categoryId],
            ]);

            // Verifica que la respuesta sea correcta
            $responseDelete->assertStatus(200);
            $responseDelete->assertJson(['message' => 'Categorías eliminadas correctamente']);


            $this->assertDatabaseMissing('categories', ['uid' => $categoryId]);
        }
    }

    /**
     * @testdox Crear Tipos de cursos exitoso*/
    public function testCreateCourseType()
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
                'name' => 'Curso de Prueba',
                'description' => 'Descripción del curso de prueba',
            ];

            // Realizar la solicitud POST
            $response = $this->postJson('/cataloging/course_types/save_course_type', $data);

            // Verifica la respuesta
            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Tipo de curso añadido correctamente',
                ]);

            // Verifica que el tipo de curso fue creado en la base de datos
            $this->assertDatabaseHas('course_types', [
                'name' => 'Curso de Prueba',
                'description' => 'Descripción del curso de prueba',
            ]);
        }
    }

    /**
     * @testdox Actualiza Tipos de cursos */
    public function testUpdatesCourseType()
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
            $response = $this->postJson('/cataloging/course_types/save_course_type', [
                'uid' => '999-12499-123456-12345-12111',
                'name' => 'Nuevo tipo de curso',
                'description' => 'Descripción del tipo de curso',

            ]);

            // Verifica quetipo de curso se haya creado correctamente
            $response->assertStatus(200)
                ->assertJson(['message' => 'Tipo de curso añadido correctamente']);

            // Obtiene el uid del tipo de curso recién creado
            $uid_tc = '999-12499-123456-12345-12111';
            $this->assertNotNull($uid_tc, 'Tipo de curso no se creó correctamente.');


            // Actualiza tipo de curso
            $data = [
                'name' => 'Tipo de curso actualizado',
                'description' => 'Descripción actualizada del tipo de curso',
            ];

            $response = $this->postJson('/cataloging/course_types/save_course_type', $data);

            // Verifica que la categoría se haya actualizado correctamente
            $response->assertStatus(200);
        }
    }

    /**
     * @testdox Elimina Tipos de cursos */
    public function testDeleteCourseType()
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

            $response = $this->postJson('/cataloging/course_types/save_course_type', [
                'uid' => '24ce6gp8-4f8e-9999-a5e0-b0a4608c1236',
                'name' => 'Tipo de curso',
                'description' => 'Descripción de tipo curso',

            ]);

            $response->assertStatus(200);
            $typeUid = '24ce6gp8-4f8e-9999-a5e0-b0a4608c1236';

            // Realiza la solicitud DELETE
            $responseDelete = $this->deleteJson('/cataloging/course_types/delete_course_types', [
                'uids' => [$typeUid],
            ]);

            // Verifica que la respuesta sea correcta
            $responseDelete->assertStatus(200);
            $responseDelete->assertJson(['message' => 'Tipos de curso eliminados correctamente']);


            $this->assertDatabaseMissing('course_types', ['uid' => $typeUid]);
        }
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
    public function testUpdatesResource()
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
            $response = $this->postJson('/cataloging/educational_resources_types/save_educational_resource_type', [
                'uid' => '555-12499-123456-12345-12111',
                'name' => 'Nuevo recurso educativo',
                'description' => 'Descripción del recurso educativo',

            ]);

            // Verifica queel recurso se haya creado correctamente
            $response->assertStatus(200)
                ->assertJson(['message' => 'Tipo de recurso educativo añadido correctamente']);

            // Obtiene el uid del recurso recién creada
            $uid_tc = '555-12499-123456-12345-12111';
            $this->assertNotNull($uid_tc, 'Tipo de recurso educativo no se creó correctamente.');


            // Actualiza el recurso
            $data = [
                'name' => 'Tipo de curso actualizado',
                'description' => 'Descripción actualizada del tipo de curso',
            ];

            $response = $this->postJson('/cataloging/educational_resources_types/save_educational_resource_type', $data);

            // Respuesta que el recurso se haya actualizado correctamente
            $response->assertStatus(200);
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
                'uid' => generate_uuid(),
                'user_uid' => $admin->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            $response = $this->postJson('/cataloging/course_types/save_course_type', [
                'uid' => '24ce9lp8-4f8e-9999-a5e0-b0a4608c1236',
                'name' => 'Recurso',
                'description' => 'Descripción Recurso',

            ]);

            $response->assertStatus(200);
            $typeUid = '24ce9lp8-4f8e-9999-a5e0-b0a4608c1236';

            // Realiza la solicitud DELETE
            $responseDelete = $this->deleteJson('/cataloging/educational_resources_types/delete_educational_resource_types', [
                'uids' => [$typeUid],
            ]);

            // Verifica que la respuesta sea correcta
            $responseDelete->assertStatus(200);
            $responseDelete->assertJson(['message' => 'Tipos de recurso educativo eliminados correctamente']);

            $this->assertDatabaseMissing('educational_resource_types', ['uid' => $typeUid]);
        }
    }

    /**
     * @testdox Crear Marco de competencias */

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
                'is_multi_select' => true,
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
                'is_multi_select' => true,
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
                'is_multi_select' => true,

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
                'is_multi_select' => true,
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
        'is_multi_select' => null,
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
        'is_multi_select' => true,
    ];

    $response = $this->postJson('/cataloging/competences_learnings_results/save_competence', $data);

    $response->assertStatus(422)
             ->assertJson(['errors' => ['parent_competence_uid' => ['La competencia padre no existe']]]);
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
        $competence->is_multi_select = false;
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

}




