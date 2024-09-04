<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\TooltipTextsModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;




class AdministrationTooltipTextTest extends TestCase {


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

/**@group Tooltip_texts
 /** @test Obtener Index View Tool Tip */
 public function testIndexReturnsViewToolTipTexts()
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
        // Hacer la solicitud GET a la ruta
        $response = $this->get(route('tooltip-texts'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que se devuelva la vista correcta
        $response->assertViewIs('administration.tooltip_texts.index');

        // Verificar que los datos se pasan a la vista
        $response->assertViewHas('page_name', 'Textos para tooltips');
        $response->assertViewHas('page_title', 'Textos para tooltips');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/tooltip_texts.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'administracion-tooltip-texts');
    }


 /** @test Crear Nuevo Tool Tip */

    public function testSaveTooltipTextCreatesNewTooltip()
    {
        // Crear un usuario y autenticar
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $tool = TooltipTextsModel::factory()->create()->first();
        $this->assertDatabaseHas('tooltip_texts', ['uid' => $tool->uid]);

        // Datos de prueba para la solicitud
        $data = [
            'uid' => $tool->uid,
            'input_id' => $tool->input_id,
            'description' => $tool->description,
        ];

        // Enviar una solicitud para guardar un nuevo texto de tooltip
        $response = $this->postJson('/administration/tooltip_texts/save_tooltip_text', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Texto de tooltip añadido correctamente']);

        // Verificar que el texto de tooltip se haya guardado en la base de datos
        $this->assertDatabaseHas('tooltip_texts', [
            'uid' => $tool->uid,
            'input_id' => $tool->input_id,
            'description' => $tool->description,
        ]);
    }

/**
 * @test Actualiza Tool Tip */
    public function testSaveTooltipTextUpdate()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $tool = TooltipTextsModel::factory()->create()->first();
        $this->assertDatabaseHas('tooltip_texts', ['uid' => $tool->uid]);

        // Datos de prueba para la solicitud
        $data = [
            'uid' => $tool->uid,
            'input_id' => $tool->input_id,
            'description' => $tool->description,
        ];

        $data['description'] = 'Nuevo texto de tooltip';

        // Enviar una solicitud para actualizar el texto de tooltip
        $response = $this->postJson('/administration/tooltip_texts/save_tooltip_text', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200);


    }

/**
 * @test  Tool Tip Error */
    public function testSaveTooltipTextValidationFails()
    {
        // Crear un usuario y autenticar
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Enviar una solicitud con datos inválidos (sin input_id ni description)
        $response = $this->postJson('/administration/tooltip_texts/save_tooltip_text', []);

        // Verificar que la respuesta sea un error de validación
        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors']);
    }

/**
 * @test Elimina Tool Tip */
    public function testDeleteTooltipTexts()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $tooltip1 = TooltipTextsModel::factory()->create()->first();
        $this->assertDatabaseHas('tooltip_texts', ['uid' => $tooltip1->uid]);


        // Enviar una solicitud para eliminar los textos de tooltip
        $response = $this->deleteJson('/administration/tooltip_texts/delete_tooltip_texts', [
            'uids' => [$tooltip1->uid],
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Textos de tooltip eliminados correctamente']);

        // Verificar que los textos de tooltip se hayan eliminado de la base de datos
        $this->assertDatabaseMissing('tooltip_texts', [
            'uid' => $tooltip1->uid,
        ]);

    }

/**
 * @test Elimina Tool Tip sin uid existente */
    public function testDeleteTooltipTextsWithNonExistingUids()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        // Intentar eliminar textos de tooltip que no existen
        $response = $this->deleteJson('/administration/tooltip_texts/delete_tooltip_texts', [
            'uids' => ['non-existing-uid-1', 'non-existing-uid-2'],
        ]);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Textos de tooltip eliminados correctamente']);

        // Verificar que no se haya agregado ningún registro en la base de datos
        $this->assertDatabaseCount('tooltip_texts', 0); // Ajusta esto si tienes otros registros
    }

/**
 * @test Obtiene una licencia específico basada en su UID. */
    public function testCanGetTooltipTextByUid()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $tooltipText = TooltipTextsModel::factory()->create()->first();
        $this->assertDatabaseHas('tooltip_texts', ['uid' => $tooltipText->uid]);


        // Enviar una solicitud para crear un nuevo sistema LMS
        $data = [
            'uid'=> $tooltipText->uid,
            'input_id' => $tooltipText->input_id,
            'description'=> $tooltipText->description,
        ];


        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/tooltip_texts/get_tooltip_text/'.$data['uid']);

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que el contenido de la respuesta es el esperado
        $response->assertJson([
            'uid'=> $tooltipText->uid,
            'input_id' => $tooltipText->input_id,
            'description'=> $tooltipText->description,
        ]);
    }

/** @test  Obtener Tool tip text con paginación*/
   public function testGetTooltipTextsWithPagination()
   {
       // Crear varios tooltip_texts en la base de datos
       TooltipTextsModel::factory()->count(5)->create();

       // Hacer una solicitud GET a la ruta sin parámetros
       $response = $this->get('/administration/tooltip_texts/get_tooltip_texts');

       // Comprobar que la respuesta es correcta
       $response->assertStatus(200);

       // Comprobar que la respuesta contiene los datos paginados
       $response->assertJsonStructure([
           'current_page',
           'data' => [
               '*' => [
                   'uid',
                   'description',
                   'input_id',

               ],
           ],
           'last_page',
           'per_page',
           'total',
       ]);
   }

/** @test  Obtener Tool tip text por Búsqueda*/
   public function testSsearchTooltipTexts()
    {
        // Crear tooltip_texts en la base de datos
        TooltipTextsModel::factory()->create(['description' => 'Texto de prueba uno']);
        TooltipTextsModel::factory()->create(['description' => 'Texto de prueba dos']);

        // Hacer una solicitud GET a la ruta con un parámetro de búsqueda
        $response = $this->get('/administration/tooltip_texts/get_tooltip_texts?search=uno');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que la respuesta contiene solo el tooltip_text que coincide con la búsqueda
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['description' => 'Texto de prueba uno']);
    }

/** @test  Obtener Tool tip text por Orden*/
    public function testSortTooltipTexts()
    {
        // Crear tooltip_texts en la base de datos
        TooltipTextsModel::factory()->create(['description' => 'Texto A']);
        TooltipTextsModel::factory()->create(['description' => 'Texto C']);
        TooltipTextsModel::factory()->create(['description' => 'Texto B']);

        // Hacer una solicitud GET a la ruta con parámetros de ordenación
        $response = $this->get('/administration/tooltip_texts/get_tooltip_texts?sort[0][field]=description&sort[0][dir]=asc&size=10');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

        // Comprobar que los tooltip_texts están ordenados correctamente
        $data = $response->json('data');
        $this->assertEquals('Texto A', $data[0]['description']);
        $this->assertEquals('Texto B', $data[1]['description']);
        $this->assertEquals('Texto C', $data[2]['description']);
    }

/** @test  Obtener todos los Tool tip text*/
    public function testGetAllTooltipTexts()
    {
        // Crear varios tooltip_texts en la base de datos
        TooltipTextsModel::factory()->create();


        // Hacer una solicitud GET a la ruta
        $response = $this->get('/administration/tooltip_texts/get_all_tooltip_texts');

        // Comprobar que la respuesta es correcta
        $response->assertStatus(200);

    }
}









