<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\LearningObjects\LearningObjetsController;

class LearningObjetsControllerTest extends TestCase
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
     * @test Verifica que se generen etiquetas correctamente.
     */
    public function testGenerateTagsReturnsTagsSuccessfully()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);
        // Autenticar al usuario
        Auth::login($user);

        // Se actualiza el modelo GeneralOptionsModel
        $general = GeneralOptionsModel::where('option_name', 'openai_key')->first();
        $general->option_value = env('OPENAI_KEY');
        $general->save();

        $generalOptionsMock = [
            'openai_key' => $general->option_value,
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        // Datos simulados del request
        $text = "This is a test description.";
        $data = ['text' => $text];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/generate_tags', $data);

        // Verificar respuesta
        $response->assertStatus(200);
    }

    public function testGenerateMetadataSuccessfully()
    {

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);
        // Autenticar al usuario
        Auth::login($user);

        // Se actualiza el modelo GeneralOptionsModel
        $general = GeneralOptionsModel::where('option_name', 'openai_key')->first();
        $general->save();

        $generalOptionsMock = [
            'openai_key' => $general->option_value,
        ];
        // Asignar el mock a app('general_options')
        App::instance('general_options', $generalOptionsMock);

        // Datos simulados del request
        $text = "This is a test description for metadata.";
        $data = ['text' => $text];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/generate_metadata', $data);

        // Verificar respuesta
        $response->assertStatus(200);
    }
}
