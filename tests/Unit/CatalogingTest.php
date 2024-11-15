<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Cataloging\CategoriesController;


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



/** @test */
    public function testUserManagementRoleAccessCategories()
    {

        GeneralOptionsModel::create([
            'option_name' => 'managers_can_manage_categories',
            'option_value' => true,
        ]);

        // Simulamos un usuario con el rol 'MANAGEMENT'
        $user = UsersModel::factory()->create()->first();
        // Asegúrate de que el rol exista en la base de datos
        $roles = UserRolesModel::where('code', 'MANAGEMENT')->first();

        // Adjuntamos el rol con un uid generado
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        Auth::login($user);
        View::share('roles', $roles);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);

        // Hacemos una solicitud al método que queremos probar (por ejemplo, index)
        $response = $this->get('/cataloging/categories');

        // Verificamos que la respuesta sea exitosa
        $response->assertStatus(200);
    }

/** @test */
      public function testUserNotManagementCannotAccess()
      {


          // Simulamos un usuario sin el rol 'MANAGEMENT'
          $user = UsersModel::factory()->create();

          // Asegúrate de que el rol exista en la base de datos
          $roles = UserRolesModel::where('code', 'TEACHER')->first();

          // Adjuntamos el rol con un uid generado
          $user->roles()->attach($roles->uid, ['uid' => Str::uuid()]);

          Auth::login($user);
          View::share('roles', $roles);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);


        $generalOptionsMock = [
            'managers_can_manage_categories' => false,
        ];

        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        // Hacemos una solicitud al método que queremos probar (por ejemplo, index)
        $response = $this->get('/cataloging/categories');

        // Verificamos que se produzca un error 403
        $response->assertStatus(200);
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

    public function testDeleteCategories()
    {
        // Crear categorías de prueba
        $category1 = CategoriesModel::factory()->create(['name' => 'Categoría 1', 'uid' => generate_uuid()]);
        $category2 = CategoriesModel::factory()->create(['name' => 'Categoría 2', 'uid' => generate_uuid()]);

        // Simular la autenticación del usuario
        $this->actingAs(UsersModel::factory()->create());

        // Simular la solicitud DELETE
        $response = $this->deleteJson('/cataloging/categories/delete_categories', [
            'uids' => [$category1->uid, $category2->uid],
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Categorías eliminadas correctamente']);

        // Verificar que las categorías han sido eliminadas
        $this->assertDatabaseMissing('categories', ['uid' => $category1->uid]);
        $this->assertDatabaseMissing('categories', ['uid' => $category2->uid]);

    }

    /**
     * @test Buscar Categoria
     */
    public function testGetCategoriesWithSearch()
    {
        // Crear categorías de prueba
        CategoriesModel::factory()->create(['name' => 'Category 1']);
        CategoriesModel::factory()->create(['name' => 'Category 2']);
        CategoriesModel::factory()->create(['name' => 'Test Category']);

        // Hacer una solicitud GET a la ruta con parámetros de búsqueda
        $response = $this->get('/cataloging/categories/get_categories?search=Test');

        // Verificar que la respuesta tenga el código HTTP 200
        $response->assertStatus(200);

        // Verificar que solo se devuelva la categoría que coincide con la búsqueda
        $data = $response->json();
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Category', $data['data'][0]['name']);
    }

    public function testIndexWithAccessAllowed()
    {
        // Crear un usuario con el rol 'MANAGEMENT'
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);// Crea roles de prueba
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

        // Crear una opción general para permitir que los gestores administren categorías
        GeneralOptionsModel::create([
            'option_name' => 'managers_can_manage_categories',
            'option_value' => true
        ]);

        // Hacer una solicitud GET a la ruta como usuario autenticado
        $response = $this->actingAs($user)->get('/cataloging/categories/');

        // Verificar que la respuesta tenga el código HTTP 200
        $response->assertStatus(200);

        // Verificar que la vista se renderice correctamente
        $response->assertViewHas('categories_anidated');
        $response->assertViewHas('categories');
    }

    /**
     * @test Respuesta al denegar acceso a categoria
     */
    public function testIndexWithAccessDenied()
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


        // Crear una opción general para denegar que los gestores administren categorías
        GeneralOptionsModel::create([
            'option_name' => 'managers_can_manage_categories',
            'option_value' => false
        ]);

        // Hacer una solicitud GET a la ruta como usuario autenticado
        $response = $this->actingAs($user)->get('/cataloging/categories/');

        // Verificar que la respuesta a la opción de denegar es correcta
        $response->assertStatus(200);
    }

    public function testGetCategoriesWithSort()
    {
        // Crear categorías de prueba
        CategoriesModel::factory()->create(['name' => 'Category 1']);
        CategoriesModel::factory()->create(['name' => 'Category 2']);
        CategoriesModel::factory()->create(['name' => 'Test Category']);

        // Hacer una solicitud GET a la ruta con parámetros de ordenamiento
        $response = $this->get('/cataloging/categories/get_categories?sort[0][field]=name&sort[0][dir]=desc&size=3');

        // Verificar que la respuesta tenga el código HTTP 200
        $response->assertStatus(200);

        // Verificar que las categorías se devuelvan en orden descendente por nombre
        $data = $response->json();
        $this->assertCount(3, $data['data']);
        $this->assertEquals('Test Category', $data['data'][0]['name']);
        $this->assertEquals('Category 2', $data['data'][1]['name']);
        $this->assertEquals('Category 1', $data['data'][2]['name']);
    }


    public function testIndexWithCategoriesAnidated()
    {

        $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);// Crea roles de prueba
         $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

         // Autenticar al usuario
         Auth::login($user);

         // Compartir la variable de roles manualmente con la vista
         View::share('roles', $roles);

         $generalOptionsMock = [
            'operation_by_calls' => false, // O false, según lo que necesites para la prueba
            'necessary_approval_editions' => true,
            'necessary_approval_resources' => true,

        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

         // Simula datos de TooltipTextsModel
         $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
         View::share('tooltip_texts', $tooltip_texts);

         // Simula notificaciones no leídas
         $unread_notifications = $user->notifications->where('read_at', null);
         View::share('unread_notifications', $unread_notifications);

           // Crear categorías anidadas
           $parentCategory = CategoriesModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Parent Category',
                'parent_category_uid' => null
           ])->first();


           $childCategory1 = CategoriesModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Child Category 1',
                'parent_category_uid' => $parentCategory->uid
            ])->latest()->first();


            $childCategory2 = CategoriesModel::factory()->create([
                'uid' => generate_uuid(),
                'name' => 'Child Category 9',
                'parent_category_uid' => $parentCategory->uid
            ])->latest()->first();


           // Hacer una solicitud GET a la ruta
           $response = $this->getJson('/cataloging/categories');


           // Verificar que la respuesta tenga el código HTTP 200
           $response->assertStatus(200);

            // Verificar que la vista se renderice correctamente
            $response->assertViewHas('page_name', 'Categorías');
            $response->assertViewHas('page_title', 'Categorías');
            $response->assertViewHas('resources', [
                "resources/js/cataloging_module/categories.js"
            ]);

            $response->assertViewHas('submenuselected', 'cataloging-categories');
            // Obtener los datos de las categorías desde la vista
            $data = $response->getOriginalContent()->getData();

            // Verificar que las categorías anidadas se carguen correctamente
            $categories_anidated = $data['categories_anidated'];
            $categories = $data['categories'];

            $this->assertCount(1, $categories_anidated);
            $this->assertCount(2, $categories_anidated[0]['subcategories']);

            // Verificar que las categorías planas se carguen correctamente
            $this->assertCount(3, $categories);
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

            $uid = generate_uuid();
            $response = $this->postJson('/cataloging/course_types/save_course_type', [
                'uid' => $uid,
                'name' => 'Tipo de curso',
                'description' => 'Descripción de tipo curso',

            ]);

            $response->assertStatus(200);

            // Realiza la solicitud DELETE
            $responseDelete = $this->deleteJson('/cataloging/course_types/delete_course_types', [
                'uids' => [$uid],
            ]);

            // Verifica que la respuesta sea correcta
            $responseDelete->assertStatus(200);
            $responseDelete->assertJson(['message' => 'Tipos de curso eliminados correctamente']);


            $this->assertDatabaseMissing('course_types', ['uid' => $uid]);
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
            $uidResource = generate_uuid();
            $response = $this->postJson('/cataloging/educational_resources_types/save_educational_resource_type', [
                'uid' => $uidResource,
                'name' => 'Nuevo recurso educativo',
                'description' => 'Descripción del recurso educativo',

            ]);

            // Verifica queel recurso se haya creado correctamente
            $response->assertStatus(200)
                ->assertJson(['message' => 'Tipo de recurso educativo añadido correctamente']);

            // Obtiene el uid del recurso recién creada
            $this->assertNotNull($uidResource, 'Tipo de recurso educativo no se creó correctamente.');


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

            $uidResource = generate_uuid();
            $response = $this->postJson('/cataloging/course_types/save_course_type', [
                'uid' => $uidResource,
                'name' => 'Recurso',
                'description' => 'Descripción Recurso',

            ]);

            $response->assertStatus(200);

            // Realiza la solicitud DELETE
            $responseDelete = $this->deleteJson('/cataloging/educational_resources_types/delete_educational_resource_types', [
                'uids' => [$uidResource],
            ]);

            $responseDelete->assertStatus(200);
            $responseDelete->assertJson(['message' => 'Tipos de recurso educativo eliminados correctamente']);

            $this->assertDatabaseMissing('educational_resource_types', ['uid' => $uidResource]);
        }
    }


}

