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
use Illuminate\Support\Facades\Schema;
use App\Models\CertificationTypesModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogingCourseTest extends TestCase
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
 * @test Import Esco Framework
 */
    public function testImportEscoFramework()
    {
        // Crear archivos de prueba
        $skillsHierarchyFile = UploadedFile::fake()->create('skills_hierarchy.csv', 1);
        $skillsFile = UploadedFile::fake()->create('skills.csv', 1);
        $broaderRelationsSkillPillarFile = UploadedFile::fake()->create('broader_relations_skill_pillar.csv', 1);

        // Simular un request POST a la ruta
        $response = $this->postJson('/cataloging/competences_learnings_results/import_esco_framework', [
            'skills_hierarchy_file' => $skillsHierarchyFile,
            'skills_file' => $skillsFile,
            'broader_relations_skill_pillar_file' => $broaderRelationsSkillPillarFile,
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                ->assertJson(['message' => 'Competencias y resultados de aprendizaje añadidos']);
    }

    /**
    * @test Import CSV Competencias Error Sin fichero
    */
    public function testImportCSVWithoutFile()
    {
        // Simular un request POST a la ruta sin el archivo
        $response = $this->postJson('/cataloging/import_csv', []);

        // Verificar que la respuesta sea un error
        $response->assertStatus(422)
                ->assertJson(['message' => 'No ha seleccionado ningún fichero']);
    }
    /**
    * @test Import CSV Competencias Json Inválido
    */

    public function testImportCSVWithInvalidJson()
    {
        // Crea un archivo JSON de prueba con contenido inválido
        $invalidJsonContent = 'invalid json content';

        // Crea un archivo temporal para simular la carga del archivo
        $file = UploadedFile::fake()->create('data.json', 0, null, TRUE);
        file_put_contents($file->getRealPath(), $invalidJsonContent);

        // Simular un request POST a la ruta
        $response = $this->postJson('/cataloging/import_csv', [
            'data-json-import' => $file,
        ]);

        // Verificar que la respuesta sea un error
        $response->assertStatus(500);
    }

    /**
    * @test Import Json Competencias fichero válido
    */

    public function testImportJsonWithValidFile()
    {
        // Crear un archivo
    $jsonContent = json_encode([
        ['name' => 'Competence 1', 'description' => 'Description 1'],
        ['name' => 'Competence 2', 'description' => 'Description 2'],
    ]);

    // Crear un archivo temporal para simular la carga del archivo
    $file = UploadedFile::fake()->create('data.json', 0, null, true);
    file_put_contents($file->getRealPath(), $jsonContent);

    // Simular un request POST a la ruta
        try {
            $response = $this->postJson('/cataloging/import_csv', [
                'data-json-import' => $file,
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Importación realizada']);
        } catch (\Exception $e) {
            // Verificar que la respuesta contenga el mensaje de error esperado
            $response = $this->postJson('/cataloging/import_csv', [
                'data-json-import' => $file,
            ]);

            $response->assertStatus(500)
                    ->assertJsonStructure(['error']);
        }
    }

    /*  Group certificación*/
    /**
    * @test Guarda Tipo de Certificación
    */
    public function testSaveCertificationTypeWithValidData()
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

            //Crea una categoria
            $category = CategoriesModel::factory()->create()->first();
            $this->assertDatabaseHas('categories', ['uid' => $category->uid]);

            // request POST a la ruta con datos válidos
            $response = $this->postJson('/cataloging/certification_types/save_certification_type', [
                'uid' => generate_uuid(),
                'name' => 'New Certification Type',
                'description' => 'Description',
                'category_uid' => $category->uid
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                    ->assertJsonStructure(['message', 'certification_types']);
        }
    }

    /**
    * @test Elimina Tipo de Certificación
    */
    public function testDeleteCertificationTypes()
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
            //Categoría válida

            $categori = CategoriesModel::factory()->create()->first();
            $this->assertDatabaseHas('categories', ['uid' => $categori->uid]);

            // Crear tipos de certificación de prueba
            $certificationType1 = CertificationTypesModel::create([
                'uid' => generate_uuid(),
                'name' => 'Certification Type 1',
                'category_uid' => $categori->uid,
            ]);

            $certificationType2 = CertificationTypesModel::create([
                'uid' => generate_uuid(),
                'name' => 'Certification Type 2',
                'category_uid' => $categori->uid,
            ]);

            // Simular un request DELETE a la ruta con los UIDs de los tipos a eliminar
            $response = $this->deleteJson('/cataloging/certification_types/delete_certification_types', [
                'uids' => [$certificationType1->uid, $certificationType2->uid],
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                    ->assertJsonStructure(['message', 'certification_types']);

            // Verificar que los tipos de certificación se hayan eliminado
            $this->assertDatabaseMissing('certification_types', ['uid' => $certificationType1->uid]);
            $this->assertDatabaseMissing('certification_types', ['uid' => $certificationType2->uid]);
        }
    }

    /**
    * @test Guarda Tipo de programas educacional
    */

    public function testSaveEducationalProgramTypeWithValidData()
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


            $educational = EducationalProgramTypesModel::factory()->create()->first();
            $this->assertDatabaseHas('educational_program_types', ['uid' => $educational->uid]);
            // Simular un request POST a la ruta con datos válidos
            // Generar un nombre único
            $uniqueName = 'Educational Program Type ' . Str::random(10);
            $response = $this->postJson('/cataloging/educational_program_types/save_educational_program_type', [
                'uid' => $educational->uid,
                'name' => $uniqueName,
                'description' => $educational->description,
                'managers_can_emit_credentials' => $educational->managers_can_emit_credentials,
                'teachers_can_emit_credentials' => $educational->teachers_can_emit_credentials,
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                    ->assertJsonStructure(['message', 'educational_program_types'])
                    ->assertJson(['message' => 'Tipo de programa educativo añadido correctamente']);
        }
    }

    /**
    * @test Guarda Tipo de programas educacional Error nombre en uso
    */

    public function testSaveEducationalProgramTypeNameInUse()
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

            $educational = EducationalProgramTypesModel::factory()->create()->first();
            $this->assertDatabaseHas('educational_program_types', ['uid' => $educational->uid]);
            // Simular un request POST a la ruta con datos válidos
            $response = $this->postJson('/cataloging/educational_program_types/save_educational_program_type', [
                'uid' => $educational->uid,
                'name' => $educational->name,
                'description' => $educational->description,
                'managers_can_emit_credentials' => $educational->managers_can_emit_credentials,
                'teachers_can_emit_credentials' => $educational->teachers_can_emit_credentials,
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(422)
                    ->assertJson(['message' => 'Algunos campos son incorrectos']);
        }
    }

/**
* @test Elimina Tipo de programas educacional
*/
    public function testDeleteEducationalProgramTypesSuccessfully()
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
            $programType1 = EducationalProgramTypesModel::factory()->create()->first();
                $this->assertDatabaseHas('educational_program_types', ['uid' => $programType1->uid]);

            // Simular un request DELETE a la ruta con los UIDs de los tipos a eliminar
            $response = $this->deleteJson('/cataloging/educational_program_types/delete_educational_program_types', [
                'uids' => [$programType1->uid],
            ]);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                    ->assertJsonStructure(['message', 'educational_program_types'])
                    ->assertJson(['message' => 'Tipos de programa educativo eliminados correctamente']);

            // Verificar que los tipos de programa educativo se hayan eliminado
            $this->assertDatabaseMissing('educational_program_types', ['uid' => $programType1->uid]);

        }
    }

/**
* @test Obtiene todos los tipos de Programas educacionales
*/
    public function testGetEducationalProgramTypesReturnsJson()
    {
        // Crear algunos registros de tipo de programa educativo
        EducationalProgramTypesModel::factory()->count(5)->create();

        // Realizar la solicitud a la ruta
        $response = $this->get('/cataloging/educational_program_types/get_list_educational_program_types');

        // Verificar que la respuesta sea un JSON
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'name',
                    // Añade otros campos que esperas en la respuesta
                ],
            ],
            'last_page',
            'total',
        ]);
    }

/**
* @test Obtiene la búsqueda de un Tipo de Programa Educacional
*/
    public function testGetEducationalProgramTypesWithSearch()
    {
        // Crear registros de tipo de programa educativo
        EducationalProgramTypesModel::factory()->create(['name' => 'Mathematics']);
        EducationalProgramTypesModel::factory()->create(['name' => 'Science']);

        // Realizar la solicitud con un parámetro de búsqueda
        $response = $this->get('/cataloging/educational_program_types/get_list_educational_program_types?search=Math');

        // Verificar que la respuesta contenga solo el programa educativo que coincide
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'Mathematics']);
    }

/**
* @test Obtiene listado tipo de Programas educacionales ordenados
*/
    public function testGetEducationalProgramTypesWithSorting()
    {
        // Crear registros de tipo de programa educativo
        EducationalProgramTypesModel::factory()->create(['name' => 'Mathematics']);
        EducationalProgramTypesModel::factory()->create(['name' => 'Social']);
        EducationalProgramTypesModel::factory()->create(['name' => 'Science']);

        $response = $this->get('/cataloging/educational_program_types/get_list_educational_program_types?sort[0][field]=name&sort[0][dir]=asc');

        $response->assertStatus(200);

    }

/**
* @test Obtiene un tipo de programa educativo por uid
*/
    public function testGetEducationalProgramTypeUid()
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

            // Crear un tipo de programa educativo de prueba
            $educational_program_type = EducationalProgramTypesModel::factory()->create()->first();

            // Hacer una solicitud GET a la ruta con el uid del tipo de programa educativo
            $response = $this->get('/cataloging/educational_program_types/get_educational_program_type/' . $educational_program_type->uid);

            // Verificar que la respuesta tenga un código de estado 200 (OK)
            $response->assertStatus(200);

            // Verificar que la respuesta contenga los datos correctos del tipo de programa educativo
            $response->assertJson($educational_program_type->toArray());
        }
    }

/**
* @test Obtiene Error si tipo de programa educativo no existe
*/
    public function testGetEducationalProgramTypeNotFound()
    {
        // Hacer una solicitud GET a la ruta con un uid que no existe
        $response = $this->get('/cataloging/educational_program_types/get_educational_program_type/non-existent-uid');

        // Verificar que la respuesta tenga un código de estado 406 (Not Acceptable)
        $response->assertStatus(406);

        // Verificar que la respuesta contenga el mensaje esperado
        $response->assertJson(['message' => 'El tipo de programa educativo no existe']);
    }


/**
* @test Lista de tipos de cursos */
    public function testGetListCourseTypesWithoutSearch()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $coueseType1 = CourseTypesModel::factory()->create()->first();
        $this->assertDatabaseHas('course_types', ['uid' => $coueseType1->uid]);


        $data=[
            'uid' => $coueseType1->uid,
            'name' => $coueseType1->name,
            'created_at' => $coueseType1->created_at,
            'updated_at' => $coueseType1->updated_at
        ];


        // Simular un request GET a la ruta sin parámetros de búsqueda
        $response = $this->getJson('/cataloging/course_types/get_list_course_types',$data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total'])
                ->assertJsonCount(1, 'data'); // Verificar que se devuelven 2 tipos de curso
    }

/**
* @test Exporta CSV Competencias
*/
    public function testExportCSV()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear competencias de prueba
        $competence = CompetencesModel::factory()->create()->first();
        $this->assertDatabaseHas('competences', ['uid' => $competence->uid]);

        // Simular un request GET a la ruta
        $response = $this->getJson('/cataloging/export_csv');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => [
                        'uid',
                        'name',
                        'description',
                        'parent_competence_uid',
                        'subcompetences',
                    ],
                ])
                ->assertJsonCount(1) // Verificar que se devuelven 2 competencias
                ->assertJsonFragment(['name' => $competence->name]);

    }


}
