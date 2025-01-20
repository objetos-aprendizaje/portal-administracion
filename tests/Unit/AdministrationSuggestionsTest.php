<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
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
use App\Models\RedirectionQueriesLearningObjectsModel;
use App\Models\RedirectionQueriesEducationalProgramTypesModel;

class AdministrationSuggestionsTest extends TestCase
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

    /**
     * @test Obtener Index View*/

    public function testIndexViewSuggestionsImprovements()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generateUuid()]); // Crea roles de prueba
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
            'uid' => $emailuid,
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

        // Arrange: Create a user and act as them
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Create an email entry
        $email1 = SuggestionSubmissionEmailsModel::factory()->create();

        // Assert that the email exists in the database
        $this->assertDatabaseHas('suggestion_submission_emails', ['uid' => $email1->uid]);

        // Prepare data for deletion with the correct key
        $data = [
            'uidsEmails' => [$email1->uid], // Ensure this matches the method's input key
        ];

        // Act: Send a delete request
        $response = $this->postJson('/administration/suggestions_improvements/delete_emails', $data);

        // Assert: Check response status and message
        $response->assertStatus(200)
            ->assertJson(['message' => 'Emails eliminados correctamente']);

        // Assert: Check that the email has been deleted from the database
        $this->assertDatabaseMissing('suggestion_submission_emails', ['uid' => $email1->uid]);
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
            'uidsEmails' => [generateUuid(), generateUuid()], // Assuming these IDs do not exist
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


}
