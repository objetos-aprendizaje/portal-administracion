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

        // Mock del método callOpenAI
        // $this->partialMock(LearningObjetsController::class, function ($mock) {
        //     $mock->shouldReceive('callOpenAI')
        //          ->once()
        //          ->andReturn(['tag1', 'tag2', 'tag3']);
        // });

        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]); // Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);
        // Autenticar al usuario
        Auth::login($user);

        // Se actualiza el modelo GeneralOptionsModel
        $general = GeneralOptionsModel::where('option_name', 'openai_key')->first();
        $general->option_value = "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";
        $general->save();

        $generalOptionsMock = [           
            'openai_key'=> $general->option_value,       
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

        // $response->assertJson(['tag1', 'tag2', 'tag3']);
    }
}
