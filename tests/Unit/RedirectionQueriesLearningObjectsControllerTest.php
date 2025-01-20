<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\RedirectionQueriesLearningObjectsModel;

class RedirectionQueriesLearningObjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox Inicialización de inicio de sesión
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /** @test redirección Programa educativos */
    public function testRedirectionQueryProgramsEducational()
    {
        // Crear un usuario de prueba y asignar roles
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

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

        // Simular la ruta
        $response = $this->get(route('redirection-queries-educational-program-types'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta fue cargada
        $response->assertViewIs('administration.redirection_queries_educational_program_types.index');

        // Verificar que los datos de la vista sean los correctos
        $response->assertViewHas('page_name', 'Redirección de consultas');
        $response->assertViewHas('page_title', 'Redirección de consultas');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/redirection_queries_educational_program_types.js",
            "resources/js/modal_handler.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'redirection-queries-educational-program-types');
    }

    /**
     *  @test  Guardar Redirección */
    public function testSaveRedirection()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $educational_type = EducationalProgramTypesModel::factory()->create();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $data = [
            'educational_program_type_uid' => $educational_type->uid,
            'type' => 'web',
            'course_type_uid' => $course->course_type_uid,
            'learning_object_type' => 'COURSE',
            'contact' => 'https://test.com'
        ];
        // Enviar la solicitud POST
        $response = $this->postJson('/administration/redirection_queries_learning_objects/save_redirection_query', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Redirección guardada correctamente']);
    }

    /**
     *  @test  Guardar Redirección con redirection_query_uid*/
    public function testSaveRedirectionWithRdirectionQueryUid()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $redirect = RedirectionQueriesLearningObjectsModel::factory()->create(
            [
                'type' =>  'web',
                'learning_object_type' => 'EDUCATIONAL_PROGRAM'
            ]
        );

        $educational_type = EducationalProgramTypesModel::factory()->create();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();

        $data = [
            'redirection_query_uid' =>  $redirect->uid,
            'educational_program_type_uid' => $educational_type->uid,
            'type' => 'web',
            'course_type_uid' => $course->course_type_uid,
            'learning_object_type' => 'EDUCATIONAL_PROGRAM',
            'contact' => 'https://test.com'
        ];
        // Enviar la solicitud POST
        $response = $this->postJson('/administration/redirection_queries_learning_objects/save_redirection_query', $data);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Redirección guardada correctamente']);
    }


    /**
     *  @test  Redirección consulta Error*/
    public function testSaveRedirectionQueryError()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        RedirectionQueriesLearningObjectsModel::factory()->create();
        $educational_type = EducationalProgramTypesModel::factory()->create();

        $data = [
            'educational_program_type_uid' => $educational_type->uid,
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/redirection_queries_learning_objects/save_redirection_query', $data);

        // Verificar la respuesta
        $response->assertStatus(422)
            ->assertJson(['message' => 'Hay campos incorrectos']);
    }


    /**
     *  @test  Redirección consulta Error*/
    public function testSaveRedirectionQueryValidationErrors()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Datos de prueba inválidos (sin educational_program_type_uid)
        $data = [
            'type' => 'web',
            'contact' => 'not-a-url',
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/redirection_queries_learning_objects/save_redirection_query', $data);

        // Verificar la respuesta de error
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Hay campos incorrectos',
                "errors" => [
                    "learning_object_type" => [
                        "El tipo de objeto es obligatorio",
                    ],
                    "contact" => [
                        "El contacto debe ser una URL válida."
                    ],
                ]
            ]);
    }

    /**
     *  @test  Redirección consulta email invalido*/
    public function testSaveRedirectionQueryInvalidEmail()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Datos de prueba inválidos (tipo email pero contacto no es un email válido)
        $data = [
            'educational_program_type_uid' => 'valid-uid',
            'type' => 'email',
            'contact' => 'not-an-email',
        ];

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/redirection_queries_learning_objects/save_redirection_query', $data);

        // Verificar la respuesta de error
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Hay campos incorrectos',
                'errors' => [
                    'contact' => ['El contacto debe ser un correo electrónico válido.'],
                ],
            ]);
    }

    /**
     *  @test  Borrar Redirección consulta */
    public function testDeleteRedirectionQuery()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        $redirect = RedirectionQueriesLearningObjectsModel::factory()->create();

        // Datos de prueba válidos
        $data = [
            'uids'=>[
                $redirect->uid
            ]
        ];

        // Envia la solicitud DELETE para eliminar las redirecciones
        $response = $this->deleteJson('/administration/redirection_queries_learning_objects/delete_redirections_queries', $data);

        // Verifica la respuesta
        $response->assertStatus(200)
            ->assertJson(['message' => 'Redirecciones eliminadas correctamente']);
    }

    /**
     *  @test  Obtener page redirección consulta Programa Educacional con paginación*/
    public function testGetRedirectionsQueriesWithPagination()
    {
        // Crear tipos de programas educativos
        $educationalProgram = EducationalProgramTypesModel::factory()->create()->first();

        // Crear redirecciones en la base de datos

        RedirectionQueriesLearningObjectsModel::factory()->create(
            [
                'educational_program_type_uid' => $educationalProgram->uid,
                'type' => 'email',
                'contact' => 'email@example.com',
            ]
        );

        // Hacer una solicitud GET a la ruta

        $response = $this->get('/administration/redirection_queries_learning_objects/get_redirections_queries');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene los datos paginados
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'contact',
                    'educational_program_type_uid',
                    'type',
                    'contact',
                ],
            ],
            'last_page',
            'per_page',
            'total',
        ]);
    }

     /**
     *  @test  Obtener page redirección consulta Programa Educacional por búsqueda*/
    public function testSearchRedirectionsQueries()
    {
        // Crear tipos de programas educativos
        $programType = EducationalProgramTypesModel::factory()->create(['name' => 'Programa A'])->first();

        // Crear redirecciones en la base de datos
        RedirectionQueriesLearningObjectsModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'contact@example.com'
        ]);
        RedirectionQueriesLearningObjectsModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'another_contact@example.com'
        ]);

        // Hacer una solicitud GET a la ruta con un parámetro de búsqueda
        $response = $this->get('/administration/redirection_queries_learning_objects/get_redirections_queries?search=contact');

        // Comprueba que la respuesta es correcta
        $response->assertStatus(200);
    }

    /** @test  verifica el orden de las redirecciones de las conultas programas educativos*/
    public function testSortRedirectionsQueries()
    {
        // Crear tipos de programas educativos
        $programType = EducationalProgramTypesModel::factory()->create(['name' => 'Programa A'])->first();

        // Crear redirecciones en la base de datos
        RedirectionQueriesLearningObjectsModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'B_contact@example.com'
        ]);
        RedirectionQueriesLearningObjectsModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'A_contact@example.com'
        ]);

        // Hacer una solicitud GET a la ruta con parámetros de ordenación
        $response = $this->get('/administration/redirection_queries_learning_objects/get_redirections_queries?sort[0][field]=contact&sort[0][dir]=asc&size=10');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que los registros devueltos están ordenados correctamente
        $data = $response->json('data');
        // Verificar que hay al menos 2 registros
        $this->assertCount(2, $data);

        // Comprobar el orden de los registros
        $this->assertEquals('A_contact@example.com', $data[0]['contact']);
        $this->assertEquals('B_contact@example.com', $data[1]['contact']);
    }

    /** @test  Obtiene Redirección por uid*/
    public function testGetRedirectionQueryByUid()
    {
        // Crear un tipo de programa educativo
        $programType1 = EducationalProgramTypesModel::factory()->create()->first();

        // Crear una redirección en la base de datos
        $redirectionQuery = RedirectionQueriesLearningObjectsModel::factory()->create([
            'uid' => generateUuid(),
            'educational_program_type_uid' => $programType1->uid,
            'contact' => 'contact@example.com'
        ])->first();

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/redirection_queries_learning_objects/get_redirection_queries/' . $redirectionQuery->uid);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);


        $data = $response->json();

        $this->assertEquals($redirectionQuery->uid, $data['uid']);
        $this->assertEquals($redirectionQuery->contact, $data['contact']);
        $this->assertEquals($programType1->uid, $data['educational_program_type']['uid']);
        $this->assertEquals($programType1->name, $data['educational_program_type']['name']);
    }

}
