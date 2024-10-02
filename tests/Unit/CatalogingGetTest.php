<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Http\Response;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\CourseTypesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\CertificationTypesModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Cataloging\CategoriesController;

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


        $data = [
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
        $response = $this->getJson('/cataloging/certification_types/get_list_certification_types', []);

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
        $uid = generate_uuid();
        $response = $this->getJson('/cataloging/certification_types/get_certification_type/' . $uid);

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
        $competencesCount = 4;
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


    public function testGetCompetencesWithSearchAndSort()
    {
        // Crear algunos registros de competencia en la base de datos
        CompetencesModel::factory()->create(['name' => 'Mathematics']);
        CompetencesModel::factory()->create(['name' => 'Science']);
        CompetencesModel::factory()->create(['name' => 'History']);

        // Simular una solicitud GET con parámetros de búsqueda y ordenación
        $response = $this->get('/cataloging/competences_learnings_results/get_competences?search=Math&sort[0][field]=name&sort[0][dir]=asc');

        // Verificar que la respuesta sea un éxito (código 200)
        $response->assertStatus(200);

        // Verificar que la respuesta sea un JSON
        $response->assertJsonStructure([
            'data' => [
                '*' => ['uid', 'name', 'parent_competence_uid'], // Asegúrate de incluir los campos correctos
            ],
            'links',

        ]);

        // Verificar que el resultado contenga solo la competencia que coincide con la búsqueda
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Mathematics', $response->json('data.0.name'));

        // Verificar que los resultados estén ordenados correctamente
        $response = $this->get('/cataloging/competences_learnings_results/get_competences?search=&sort[0][field]=name&sort[0][dir]=asc');

    }


    /** @test Obtener categorias con paginación*/
    public function testCategoriesWithPagination()
    {

        CategoriesModel::factory()->count(5)->create();

        // Realiza una solicitud GET a la ruta que invoca el método getCategories
        $response = $this->getJson('/cataloging/categories/get_categories');

        // Verifica que la respuesta sea exitosa (código 200)
        $response->assertStatus(200);

        // Verifica que la respuesta contenga datos paginados
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'name',
                    'parent_category_uid',
                    'created_at',
                    'updated_at',
                ],
            ],
            'first_page_url',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    /**
     * @test
     * Verifica que el método index() del CertificationTypesController
     * retorna la vista correcta con los datos necesarios.
     */
    public function testLoadsTheCertificationTypesViewWithProperData()
    {

        // Crear un usuario de prueba
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

        // Crear algunas categorías de prueba, incluyendo subcategorías
        $category = CategoriesModel::factory()->create(['parent_category_uid' => null])->first();
        $subcategory = CategoriesModel::factory()->create(['parent_category_uid' => $category->uid]);

        // Crear algunos tipos de certificación de prueba
        $certificationType1 = CertificationTypesModel::factory()->create(
            [
                'category_uid' => $category->uid,
            ]
        )->first();


        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('cataloging-certification-types'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('cataloging.certification_types.index');

        // Verificar que la vista tiene los datos correctos para 'certification_types'
        $response->assertViewHas('certification_types', function ($viewData) use ($certificationType1) {
            return in_array($certificationType1->toArray(), $viewData);
        });

        // Verificar que la vista tiene al menos una categoría con una subcategoría
        $response->assertViewHas('categories', function ($viewData) use ($category, $subcategory) {
            return !empty($viewData) &&
                $viewData[0]['uid'] === $category->uid &&
                !empty($viewData[0]['subcategories']) &&
                $viewData[0]['subcategories'][0]['parent_category_uid'] == $category->uid;
        });

        // Verificar que otros datos están presentes en la vista
        $response->assertViewHas('page_name', 'Tipos de certificación');
        $response->assertViewHas('page_title', 'Tipos de certificación');
    }

    /**
     * @test
     * Verifica que el método index() del CategoriesController
     * redirige a la vista de acceso denegado si el usuario no tiene acceso.
     */
    public function testRedirectsToAccessNotAllowedWhenAccessIsDenied()
    {
        // Crear un usuario con el rol 'MANAGEMENT' y autenticarlo
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Denegar a los gestores manejar categorías
        GeneralOptionsModel::factory()->create([
            'option_name' => 'managers_can_manage_categories',
            'option_value' => '0'
        ]);

        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('cataloging-categories'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la de acceso denegado
        $response->assertViewIs('access_not_allowed');

        // Verificar que la vista tiene los datos correctos
        $response->assertViewHas('title', 'No es posible administrar las categorías');
        $response->assertViewHas('description', 'El administrador tiene bloqueado la administración de categorías a los gestores.');
    }

    /**
     * @test
     * Verifica que el método index() del CompetencesLearningsResultsController
     * retorna la vista correcta con los datos necesarios.
     */
    public function testLoadsTheCompetencesLearningsResultsViewWithProperData()
    {

        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);


        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('cataloging-competences-learning-results'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('cataloging.competences_learnings_results.index');

        // Verificar que la vista tiene los datos correctos para 'page_name' y 'page_title'
        $response->assertViewHas('page_name', 'Competencias y resultados de aprendizaje');
        $response->assertViewHas('page_title', 'Competencias y resultados de aprendizaje');

        // Verificar que la vista tiene los recursos de JavaScript correctos
        $response->assertViewHas('resources', function ($resources) {
            return in_array('resources/js/cataloging_module/competences_learnings_results.js', $resources);
        });

        // Verificar que otras opciones están presentes en la vista
        $response->assertViewHas('coloris', true);
        $response->assertViewHas('submenuselected', 'cataloging-competences-learning-results');
        $response->assertViewHas('infiniteTree', true);
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
            $role->uid => ['uid' => generate_uuid()]
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
            $role->uid => ['uid' => generate_uuid()]
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
     * Verifica que el método index() del EducationalProgramTypesController
     * retorna la vista correcta con los datos necesarios.
     */
    public function testLoadsTheEducationalProgramTypesViewWithProperDataEPT()
    {
        // Crear un usuario y asignarle el rol 'MANAGEMENT'
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        View::share('roles', $user->roles->toArray());

        $tooltip_texts = TooltipTextsModel::get();
        View::share('tooltip_texts', $tooltip_texts);

        // Crear algunos tipos de programas educativos de prueba
        $programType1 = EducationalProgramTypesModel::factory()->create();
        $programType2 = EducationalProgramTypesModel::factory()->create();

        // Realizar la solicitud GET a la ruta correspondiente
        $response = $this->get(route('cataloging-educational-program-types'));

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la vista cargada es la correcta
        $response->assertViewIs('cataloging.educational_program_types.index');

        // Verificar que la vista tiene los datos correctos para 'educational_program_types'
        $response->assertViewHas('educational_program_types', function ($viewData) use ($programType1, $programType2) {
            return in_array($programType1->toArray(), $viewData) &&
                   in_array($programType2->toArray(), $viewData);
        });

        // Verificar que otros datos están presentes en la vista
        $response->assertViewHas('page_name', 'Tipos de programas formativos');
        $response->assertViewHas('page_title', 'Tipos de programas formativos');
        $response->assertViewHas('submenuselected', 'cataloging-educational-program-types');
        $response->assertViewHas('tabulator', true);
    }


    protected function checkAccessUserCategories()
    {
        return true; // Forzamos a que devuelva true para las pruebas
    }
    /** @test */
    public function it_displays_categories_and_nested_categories()
    {
        $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);// Crea roles de prueba
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

        // Creamos una instancia del controlador de prueba
        $controller = new CategoriesController();


        // Creamos algunas categorías para la prueba
        $parentCategory = CategoriesModel::factory()->create([
            'uid' => generate_uuid(),
            'parent_category_uid' => null
        ])->first();
        CategoriesModel::factory()->create([
            'uid' => generate_uuid(),
            'parent_category_uid' => $parentCategory->uid
        ])->first();


        // Simulamos una solicitud a la ruta de index
        $response = $this->get('/cataloging/categories');

        // Verificamos que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificamos que las categorías se pasen a la vista
        $categories = CategoriesModel::all()->toArray();
        $this->assertNotNull($categories);
        // Verificamos que categories_anidated esté presente en la vista
        CategoriesModel::whereNull('parent_category_uid')->with('subcategories')->get()->toArray();

    }

}







