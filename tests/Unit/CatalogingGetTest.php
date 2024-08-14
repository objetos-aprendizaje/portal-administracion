<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Http\Response;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\CourseTypesModel;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Schema;
use App\Models\CertificationTypesModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Cataloging\CompetencesLearningsResultsController;


class CatalogingGetTest extends TestCase
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
* @test Obtiene todas los tipo de cursos.
*/

    public function testGetCourseType()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $courseType1 = CourseTypesModel::factory()->create()->first();
        $this->assertDatabaseHas('course_types', ['uid' => $courseType1->uid]);


        $data=[
            'uid' => $courseType1->uid,
            'name' => $courseType1->name,
            'created_at' => $courseType1->created_at,
            'updated_at' => $courseType1->updated_at
        ];

        // Simular un request GET a la ruta con el UID del tipo de curso
        $response = $this->getJson('/cataloging/course_types/get_course_type/' . $data['uid']);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'uid',
                    'name',
                    'created_at',
                    'updated_at'
                ]);
    }

    /**
    * @test Obtener todos los tipos de certificación
    */
    public function testGetCertificationTypesWithoutFilters()
    {
    $user = UsersModel::factory()->create();
    $this->actingAs($user);

    $categoria1 = CategoriesModel::factory()->create()->first();
    $this->assertDatabaseHas('categories', ['uid' => $categoria1->uid]);

    // Crear tipos de certificación de prueba
    $certify = CertificationTypesModel::factory()->create([
        'uid' => generate_uuid(),
        'category_uid' => $categoria1->uid,
        'name' => 'Certificación de prueba 1',
        'description' => 'Certificación de prueba 1',
    ])->first();
    $this->assertDatabaseHas('certification_types', ['uid' => $certify->uid]);


    // Simular un request GET a la ruta sin filtros
    $response = $this->getJson('/cataloging/certification_types/get_list_certification_types',[]);

    // Verificar que la respuesta sea correcta
    $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'uid',
                        'name',
                        'description',
                    ],
                ],
                'last_page',
                'per_page',
                'total',
            ])
            ->assertJsonCount(1, 'data'); // Verificar que se devuelven 2 tipos de certificación
    }

    /**
    * @test Obtener certificado por ID
    */
    public function testGetCertificationTypeSucces()
    {
    $user = UsersModel::factory()->create();
    $this->actingAs($user);

    $categor1 = CategoriesModel::factory()->create()->first();
    $this->assertDatabaseHas('categories', ['uid' => $categor1->uid]);

    // Crear tipos de certificación de prueba
    $certificationType = CertificationTypesModel::factory()->create([
        'uid' => generate_uuid(),
        'category_uid' => $categor1->uid,
        'name' => 'Certificación de prueba 1',
        'description' => 'Certificación de prueba 1',
    ])->first();
    $this->assertDatabaseHas('certification_types', ['uid' => $certificationType->uid]);


    // Simular un request GET a la ruta con el UID del tipo de certificación
    $response = $this->getJson('/cataloging/certification_types/get_certification_type/' . $certificationType->uid);

    // Verificar que la respuesta sea correcta
    $response->assertStatus(200)
            ->assertJsonStructure([
                'uid',
                'name',
                'description',
            ]);
    }

    /**
    * @test Obtener certificado por ID no existente
    */
    public function testGetCertificationTypeNotFound()
    {
    $user = UsersModel::factory()->create();
    $this->actingAs($user);
    // Simular un request GET a la ruta con un UID que no existe
    $response = $this->getJson('/cataloging/certification_types/get_certification_type/non-existing-uid');

    // Verificar que la respuesta sea un error 406
    $response->assertStatus(406)
            ->assertJson([
                'message' => 'El tipo de certificación no existe',
            ]);
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
    $response = $this->get('/cataloging/educational_resources_types/get_list_educational_resource_types');

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
    $educationalresourse= EducationalResourceTypesModel::factory()->create()->first();
    $this->assertDatabaseHas('educational_resource_types', ['uid' => $educationalresourse->uid]);

    $data=[
        'uid' => $educationalresourse->uid,
    ];


    // Realizar la solicitud a la ruta con el UID
    $response = $this->get('/cataloging/educational_resources_types/get_educational_resource_type/'.$data['uid']);

    // Verificar que la respuesta sea un JSON y contenga los datos correctos
    $response->assertStatus(200);
    $response->assertJsonFragment(['uid' => $educationalresourse->uid]);
    }
    /**
    * @test Obtener Categorias por Búsqueda
    */
    public function testGetListCategoriesWithSearch()
    {
        // Crear categorías de prueba
        $category1 = CategoriesModel::factory()->create([
            'name' => 'Categoría 1',
            'description' => 'Descripción de la categoría 1',
        ]);
        $category2 = CategoriesModel::factory()->create([
            'name' => 'Categoría 2',
            'description' => 'Descripción de la categoría 2',
        ]);

        // Hacer la solicitud GET a la ruta con un término de búsqueda
        $response = $this->get('/cataloging/categories/get_list_categories?search=Categoría');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonStructure(['html']);
        $this->assertStringContainsString($category1->name, $response->json()['html']);
        $this->assertStringContainsString($category1->description, $response->json()['html']);
        $this->assertStringContainsString($category2->name, $response->json()['html']);
        $this->assertStringContainsString($category2->description, $response->json()['html']);
    }

    /**
    * @test Obtener Categorias
    */
    public function testGetListCategoriesWithoutSearch()
    {
        // Crear categorías de prueba
        $parentCategory = CategoriesModel::factory()->create()->first();
        $subcategory1 = CategoriesModel::factory()->create([
            'parent_category_uid' => $parentCategory->uid,
        ]);
        $subcategory2 = CategoriesModel::factory()->create([
            'parent_category_uid' => $parentCategory->uid,
        ]);

        // Hacer la solicitud GET a la ruta sin un término de búsqueda
        $response = $this->get('/cataloging/categories/get_list_categories');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJsonStructure(['html']);
        $this->assertStringContainsString($parentCategory->name, $response->json()['html']);
        $this->assertStringContainsString($subcategory1->name, $response->json()['html']);
        $this->assertStringContainsString($subcategory2->name, $response->json()['html']);
    }

    /**
    * @test Obtener Categorias
    */
    public function testGetCategoryReturnsCorrectData()
    {
        // Crear una categoría de prueba
        $category = CategoriesModel::factory()->create()->first();

        // Hacer la solicitud GET a la ruta
        $response = $this->get('/cataloging/categories/get_category/' . $category->uid);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson([
            'uid' => $category->uid,
            'name' => $category->name,

        ]);
    }
    /**
    * @test Obtener todas Categorias
    */
    public function testGetAllCategoriesReturnsCorrectData()
    {
        // Crear categorías de prueba
        $parentCategory1 = CategoriesModel::factory()->create()->first();

        // Crear subcategorías para la primera categoría padre
        $subcategory1 = CategoriesModel::factory()->create([
            'name' => 'Subcategoría 1',
            'parent_category_uid' => $parentCategory1->uid,
        ]);

        // GET a la ruta
        $response = $this->get('/cataloging/categories/get_all_categories');

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $categories = $response->json();

        // Verificar que las categorías padre estén en la respuesta
        $this->assertCount(1, $categories);
        $this->assertEquals($parentCategory1->name, $categories[0]['name']);

        // Verificar que las subcategorías estén incluidas
        $this->assertCount(1, $categories[0]['subcategories']);
        $this->assertEquals($subcategory1->name, $categories[0]['subcategories'][0]['name']);

    }

    /**
    * @test Obtener todas competencias
    */
    public function testGetCompetencesReturnsAllCompetences()
    {
        $competencesCount =4;
        // Crear competencias de prueba
        CompetencesModel::factory()->count($competencesCount)->create()->first();

        // Hacer la solicitud GET a la ruta
    $response = $this->get('/cataloging/competences_learnings_results/get_competences?size=4');;

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $data = $response->json();
        // Verificar que se devuelvan todas las competencias
        $this->assertCount($competencesCount, $data['data']);

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

}
