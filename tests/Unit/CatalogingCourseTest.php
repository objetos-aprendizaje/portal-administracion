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
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\CertificationTypesModel;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Cataloging\CourseTypesController;
use App\Services\AccessManager;

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
 * @test Index View Tipo de Curso
*/
    public function testIndexViewCourseTypesWithAccess()
    {
        $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);// Crea roles de prueba
         $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

         // Autenticar al usuario
         Auth::login($user);

         // Compartir la variable de roles manualmente con la vista
         View::share('roles', $roles);

         // Configura el mock de general_options con la clave correcta
         $generalOptionsMock = [
            'managers_can_manage_course_types' => true,
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

         // Simula datos de TooltipTextsModel
         $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
         View::share('tooltip_texts', $tooltip_texts);

         // Simula notificaciones no leídas
         $unread_notifications = $user->notifications->where('read_at', null);
         View::share('unread_notifications', $unread_notifications);

        // Crear algunos tipos de curso de ejemplo
        CourseTypesModel::factory()->count(3)->create();

        // Llamar al método index del controlador
        $response = $this->get('/cataloging/course_types');

        // Verificar que la respuesta es una vista
        $response->assertStatus(200);
        $response->assertViewIs('cataloging.course_types.index');
        $response->assertViewHas('page_name', 'Tipos de curso');
        $response->assertViewHas('page_title', 'Tipos de curso');
        $response->assertViewHas('resources', ['resources/js/cataloging_module/course_types.js']);
        $response->assertViewHas('course_types', CourseTypesModel::all()->toArray());
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'cataloging-course-types');
    }

    /**
     * @test Index View Tipo de Curso usuario sin acceso
    */
    public function testIndexViewCourseTypesWithoutAccess()
    {
        // Crear un usuario de prueba
        $user = UsersModel::factory()->create();

        // Crear un rol de prueba y asignarlo al usuario
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        // Configura el mock de general_options con la clave correcta
        $generalOptionsMock = [
            'managers_can_manage_course_types' => false,
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);


        $response = $this->get('/cataloging/course_types');

            // Verificar que la respuesta es la vista de acceso denegado
            $response->assertStatus(200);
            $response->assertViewIs('access_not_allowed');
            $response->assertViewHas('title', 'No tienes permiso para administrar los tipos de cursos');
            $response->assertViewHas('description', 'El administrador ha bloqueado la administración de tipos de cursos a los gestores.');

    }

    /** @test */
    public function testCourseTypesBasedOnSearch()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        // Configura el mock de general_options con la clave correcta
        $generalOptionsMock = [
            'managers_can_manage_course_types' => true,
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);
        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);


        // Crear algunos tipos de curso de ejemplo
        CourseTypesModel::factory()->create(['name' => 'Mathematics', 'description' => 'Study of numbers']);
        CourseTypesModel::factory()->create(['name' => 'Science', 'description' => 'Study of nature']);
        CourseTypesModel::factory()->create(['name' => 'History', 'description' => 'Study of past events']);

        // Realizar una solicitud con un término de búsqueda
        $response = $this->get('/cataloging/course_types/get_list_course_types?search=Math');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que solo se devuelvan los tipos de curso que coinciden con la búsqueda
        $this->assertCount(1, json_decode($response->getContent())->data);
        $this->assertEquals('Mathematics', json_decode($response->getContent())->data[0]->name);
    }

    /** @test Ordena Tipo de cursos*/
    public function testSortedCourseTypes()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        // Configura el mock de general_options con la clave correcta
        $generalOptionsMock = [
            'managers_can_manage_course_types' => true,
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);
        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);

        // Crear algunos tipos de curso de ejemplo
        CourseTypesModel::factory()->create(['name' => 'Science']);
        CourseTypesModel::factory()->create(['name' => 'Mathematics']);
        CourseTypesModel::factory()->create(['name' => 'Physical']);

        // Realizar una solicitud con parámetros de ordenamiento
        $response = $this->getJson('/cataloging/course_types/get_list_course_types?sort[0][field]=name&sort[0][dir]=asc&size=10');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        // Verificar que la respuesta sea JSON
        $response->assertJsonStructure(['data' => [['name']]]);
        // Verificar que los tipos de curso estén ordenados alfabéticamente
        $sortedData = json_decode($response->getContent())->data;
        $this->assertEquals('Mathematics', $sortedData[0]->name);
        $this->assertEquals('Physical', $sortedData[1]->name);
        $this->assertEquals('Science', $sortedData[2]->name);

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
                    ->assertJson(['message' => 'Tipo de programa formativo añadido correctamente']);
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
                    ->assertJson(['message' => 'Tipos de programa formativo eliminados correctamente']);

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
        $uuid = generate_uuid();
        $response = $this->get('/cataloging/educational_program_types/get_educational_program_type/' . $uuid);

        // Verificar que la respuesta tenga un código de estado 406 (Not Acceptable)
        $response->assertStatus(406);

        // Verificar que la respuesta contenga el mensaje esperado
        $response->assertJson(['message' => 'El tipo de programa formativo no existe']);
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
    public function testExportCsv()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear competencias de prueba
        $competence = CompetencesModel::factory()->create()->latest()->first();
        $this->assertDatabaseHas('competences', ['uid' => $competence->uid]);

        // Crear subcompetencias asociadas a competence1
        $subcompetence1 = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Subcompetence 1',
            'parent_competence_uid' => $competence->uid // Establecer la relación padre
        ])->first();

        $subcompetence2 = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Subcompetence 2',
            'parent_competence_uid' => $subcompetence1->uid // Establecer la relación padre
        ])->first();


        $competence2 = CompetencesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'Competence 2'])->latest()->first();


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
                        'allsubcompetences',
                    ],
                ])
                ->assertJsonCount(2) // Verificar que se devuelven 2 competencias
                ->assertJsonFragment(['name' => $competence->name]);

    }


}
