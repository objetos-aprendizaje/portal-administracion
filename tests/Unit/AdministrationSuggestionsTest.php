<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\UserRolesModel;
use Illuminate\Support\Carbon;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramTypesModel;
use App\Models\SuggestionSubmissionEmailsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RedirectionQueriesEducationalProgramTypesModel;

class AdministrationSuggestionsTest extends TestCase
{
    use RefreshDatabase;

/**
 * @testdox Inicialización de inicio de sesión
 */
    public function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @test Obtener Index View*/

    public function testIndexViewSuggestionsImprovements()
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

        // Simular la ruta
        $response = $this->get(route('suggestions-improvements'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta fue cargada
        $response->assertViewIs('administration.suggestions_improvements');

        // Verificar que los datos de la vista sean los correctos
        $response->assertViewHas('page_name', 'Sugerencias y mejoras');
        $response->assertViewHas('page_title', 'Sugerencias y mejoras');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/suggestions_improvements.js",
        ]);

        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'suggestions-improvements');
    }


    /**
     * @test Elimina Footer error*/
     public function testDeleteNonExistingFooterPages()
    {
        // Datos de entrada para la eliminación de un UID que no existe
        $data = [
            'uids' => Str::uuid(),
        ];

        // Realiza la solicitud DELETE
        $response = $this->deleteJson('/administration/footer_pages/delete_footer_pages', $data);

        // Verifica la respuesta
        $response->assertStatus(200) // Asegúrate de que la respuesta sea 200, ya que el método no lanza un error si no encuentra el UID
                ->assertJson(['message' => 'Páginas de footer eliminadas correctamente']);
    }

/**
*  @test  Sugerencias y mejoras*/
    public function testSavesEmailSuggestions()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $emailuid = Str::uuid();
        $data = [
            'uid'=> $emailuid,
            'email' => 'test@example.com', // Asegúrate de que sea una cadena
        ];

        // Realiza la solicitud POST a la ruta correcta
        $response = $this->postJson('/administration/suggestions_improvements/save_email', $data);

        $response->assertStatus(200)
                    ->assertJson([
                        'message' => 'Email añadido correctamente',
                    ]);

        $this->assertDatabaseHas('suggestion_submission_emails', [
            'email' => 'test@example.com',
        ]);
    }

/**
*  @test  Sugerencias y mejoras Invalido Email*/
    public function testInvalidEmailReturnsError()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $data = [
            'email' => 'invalid-email', // Email no válido
        ];

        // Realiza la solicitud POST a la ruta correcta
        $response = $this->postJson('/administration/suggestions_improvements/save_email', $data);

        // Verifica que la respuesta tenga el código de estado 406 y el mensaje esperado
        $response->assertStatus(406)
                    ->assertJson([
                        'message' => 'El email es inválido',
                    ]);
    }

/**
*  @test  Elimina email - Sugerencias y mejoras */
    public function testDeleteEmailsWithValidUids()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $email1 = SuggestionSubmissionEmailsModel::factory()->create()->first();

        $this->assertDatabaseHas('suggestion_submission_emails', ['uid' => $email1->uid]);

        $data = [
            'uids' => [$email1->uid], // Asegúrate de enviar un array con el UID
        ];

        $response = $this->postJson('/administration/suggestions_improvements/delete_emails', $data);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Emails eliminados correctamente']);

        $this->assertDatabaseMissing('suggestion_submission_emails', ['uid' => $email1->id]);

    }
/**
*  @test  Elimina email con uid invalido*/
    public function testDeleteEmailsWithInvalidUids()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Attempt to delete non-existing email IDs
        $response = $this->postJson('/administration/suggestions_improvements/delete_emails', [
            'uidsEmails' => [generate_uuid(), generate_uuid()], // Assuming these IDs do not exist
        ]);

        $response->assertStatus(200) // Assuming the method does not throw an error for non-existing IDs
                ->assertJson(['message' => 'Emails eliminados correctamente']);

        // Verify that no emails are deleted since they didn't exist
        $this->assertDatabaseCount('suggestion_submission_emails', 0); // Adjust this if you want to check for existing emails
    }
/**
*  @test  Elimina email sin uid*/
    public function testDeleteEmailsWithoutUids()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        $response = $this->postJson('/administration/suggestions_improvements/delete_emails', [
            'uidsEmails' => [],
        ]);

        $response->assertStatus(200) // Assuming the method handles empty arrays gracefully
                ->assertJson(['message' => 'Emails eliminados correctamente']);
    }

/**
*  @test  Obtener GET emails con paginación*/
    public function testGetEmailsWithPagination()
    {
        // Crear varios correos electrónicos en la base de datos
        SuggestionSubmissionEmailsModel::factory()->create(['email' => 'test1@example.com']);
        SuggestionSubmissionEmailsModel::factory()->create(['email' => 'test2@example.com']);

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/suggestions_improvements/get_emails');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene la estructura esperada
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'uid',
                    'email',
                ],
            ],
            'last_page',
            'per_page',
            'total',
        ]);
    }

/**
*  @test  Obtener buscar emails */
    public function testSearchEmails()
    {
        // Crear correos electrónicos en la base de datos
        SuggestionSubmissionEmailsModel::factory()->create(['email' => 'searchable@example.com']);
        SuggestionSubmissionEmailsModel::factory()->create(['email' => 'not_searchable@example.com']);

        // Hacer una solicitud GET a la ruta con un parámetro de búsqueda
        $response = $this->get('/administration/suggestions_improvements/get_emails?search=searchable');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

    }
/** @test Obtener orden en emails*/
    public function testSortEmails()
    {
        // Crear correos electrónicos en la base de datos
        SuggestionSubmissionEmailsModel::factory()->create(['email' => 'b@example.com']);
        SuggestionSubmissionEmailsModel::factory()->create(['email' => 'a@example.com']);

        // Hacer una solicitud GET a la ruta con parámetros de ordenación
        $response = $this->get('/administration/suggestions_improvements/get_emails?sort[0][field]=email&sort[0][dir]=asc&size=10');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que los correos electrónicos devueltos están ordenados correctamente
        $data = $response->json('data');
        $this->assertEquals('a@example.com', $data[0]['email']);
        $this->assertEquals('b@example.com', $data[1]['email']);
    }


/**
*  @test  Redirección consulta Error*/
    public function testSaveRedirectionQueryError()
    {
        // Simular un usuario autenticado
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Datos de prueba válidos
        $redirec = RedirectionQueriesEducationalProgramTypesModel::factory()->create()->first();
        $this->assertDatabaseHas('redirection_queries_educational_program_types', ['uid' => $redirec->uid]);

        // Enviar la solicitud POST
        $response = $this->postJson('/administration/redirection_queries_educational_program_types/save_redirection_query',[
            'uids' => [$redirec->uid, ]
        ]);

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
        $response = $this->postJson('/administration/redirection_queries_educational_program_types/save_redirection_query', $data);

        // Verificar la respuesta de error
        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Hay campos incorrectos',
                    'errors' => [
                        'educational_program_type_uid' => ['El tipo de programa formativo es obligatorio'],
                        'contact' => ['El contacto debe ser una URL válida.'],
                    ],
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
        $response = $this->postJson('/administration/redirection_queries_educational_program_types/save_redirection_query', $data);

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

        // Datos de prueba válidos
        $data = [
            'uid' => 'uid-id',
            'educational_program_type_uid' => 'validate-uid',
            'type' => 'email',
            'contact' => 'example@example.com',
        ];

            // Envia la solicitud DELETE para eliminar las redirecciones
            $response = $this->deleteJson('/administration/redirection_queries_educational_program_types/delete_redirections_queries',$data);

            // Verifica la respuesta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Redirección eliminada correctamente']);


    }


/**
*  @test  Obtener page redirección consulta Programa Educacional con paginación*/
    public function testGetRedirectionsQueriesWithPagination()
    {
        // Crear tipos de programas educativos
        $educationalProgram = EducationalProgramTypesModel::factory()->create()->first();

        // Crear redirecciones en la base de datos
        RedirectionQueriesEducationalProgramTypesModel::factory()->create([
            'uid' => generate_uuid(),
            'educational_program_type_uid' => $educationalProgram->uid,
            'type' => 'email',
            'contact' => 'email@example.com',

        ]);

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/redirection_queries_educational_program_types/get_redirections_queries');

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
        RedirectionQueriesEducationalProgramTypesModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'contact@example.com'
        ]);
        RedirectionQueriesEducationalProgramTypesModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'another_contact@example.com'
        ]);

        // Hacer una solicitud GET a la ruta con un parámetro de búsqueda
        $response = $this->get('/administration/redirection_queries_educational_program_types/get_redirections_queries?search=contact');

        // Comprueba que la respuesta es correcta
        $response->assertStatus(200);

    }

/** @test  verifica el orden de las redirecciones de las conultas programas educativos*/
    public function testSortRedirectionsQueries()
    {
        // Crear tipos de programas educativos
        $programType = EducationalProgramTypesModel::factory()->create(['name' => 'Programa A'])->first();

        // Crear redirecciones en la base de datos
        RedirectionQueriesEducationalProgramTypesModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'B_contact@example.com'
        ]);
        RedirectionQueriesEducationalProgramTypesModel::factory()->create([
            'educational_program_type_uid' => $programType->uid,
            'contact' => 'A_contact@example.com'
        ]);

        // Hacer una solicitud GET a la ruta con parámetros de ordenación
        $response = $this->get('/administration/redirection_queries_educational_program_types/get_redirections_queries?sort[0][field]=contact&sort[0][dir]=asc&size=10');

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
        $redirectionQuery = RedirectionQueriesEducationalProgramTypesModel::factory()->create([
            'uid' => generate_uuid(),
            'educational_program_type_uid' => $programType1->uid,
            'contact' => 'contact@example.com'
        ])->first();

        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/redirection_queries_educational_program_types/get_redirection_query/' . $redirectionQuery->uid);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);


        $data = $response->json();

        $this->assertEquals($redirectionQuery->uid, $data['uid']);
        $this->assertEquals($redirectionQuery->contact, $data['contact']);
        $this->assertEquals($programType1->uid, $data['educational_program_type']['uid']);
        $this->assertEquals($programType1->name, $data['educational_program_type']['name']);
    }

}
