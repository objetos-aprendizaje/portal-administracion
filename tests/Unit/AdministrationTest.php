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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;



class AdministrationTest extends TestCase {
    use RefreshDatabase;
    /**
     * @group configdesistema
     */

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
     * @testdox Cambio logotipo exitoso
     */
    public function testSaveLogoImage() {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        $file = UploadedFile::fake()->image('/images/custom-logos/logoprueba-1722281067.jpg');

        $response = $this->post('/administration/save_logo_image', [
            'logoPoaFile' => $file,
        ]);

        $response->assertStatus(200);

        // $response->assertJsonStructure([
        //     'message' => 'Logo actualizado correctamente',
        //     'route' => true, // Use true to indicate that 'route' should be present
        // ]);

        // Verificar que el logo se haya actualizado correctamente en la base de datos
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'poa_logo',
            'option_value' => $response->json('route'), // Asegúrate de que esto coincida con el valor esperado
        ]);
    }
    /**
     * @testdox Verifica habilitación/deshabilitación de valoraciones
     */
    public function testValoracionesPersistenEnBaseDeDatos() {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);


        $requestData = [
            'learning_objects_appraisals' => true,
            'operation_by_calls' => false,
        ];

        $response = $this->post('/administration/save_general_options', $requestData);

        $this->assertResponseStatus($response);
        $this->assertJsonResponse($response);
        $this->assertDatabaseValues();
    }

    protected function assertResponseStatus($response) {
        $response->assertStatus(200);
    }

    protected function assertJsonResponse($response) {
        $response->assertJson(['message' => 'Opciones guardadas correctamente']);
    }

    protected function assertDatabaseValues() {
        $this->assertTrue(GeneralOptionsModel::where('option_name', 'learning_objects_appraisals')->first()->option_value);
        $this->assertFalse(GeneralOptionsModel::where('option_name', 'operation_by_calls')->first()->option_value);
    }

    /**
     * @testdox Solo los admin modifican colores del tema*/
    public function test_admin_can_change_colors() {
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
            $request = new Request([
                'color1' => '#123456',
                'color2' => '#abcdef',
                'color3' => '#fedcba',
                'color4' => '#654321',
            ]);

            $response = $this->post('/administration/change_colors', $request->all());

            $response->assertStatus(200);
            $response->assertJson(['success' => true, 'message' => 'Colores guardados correctamente']);

            $this->assertDatabaseHas('general_options', ['option_name' => 'color_1', 'option_value' => '#123456']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'color_2', 'option_value' => '#abcdef']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'color_3', 'option_value' => '#fedcba']);
            $this->assertDatabaseHas('general_options', ['option_name' => 'color_4', 'option_value' => '#654321']);
        } else {
            $response = $this->post('/administration/change_colors');
            $response->assertStatus(404);
        }
    }
    /**
     * @testdox Solo los admin configuran pasarela de pago*/
    public function testadminpaymentaccess() {
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

            $response = $this->get(route('administration-payments'));

            // Verificar que la respuesta contenga la opción de menú "Pagos"
            $response->assertSee('Pagos');
            $response->assertSee('administration-payments'); // Verifica que la clase o el identificador también esté presente

        }
    }

    /**
     * @testdox Usuario no admin sin acceso a configuran pasarela de pago*/
    public function testnonadminpaymentaccess() {
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

        if ($admin->hasAnyRole(['MANAGEMENT'])) {

            $response = $this->get(route('administration-payments'));

            // Verifica que se devuelve un error 500 (Internal Server Error)
            $response->assertStatus(500);

            // Verifica que la respuesta contenga un mensaje de error específico
            $this->assertStringContainsString('Undefined variable: general_options', $response->getContent());
        }
    }


    /**
     * @group convocatorias
     */

    /**
     * @testdox Crear Convocatoria Exitoso*/
    public function testcreatecall() {

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

        if ($admin->hasAnyRole(['MANAGEMENT'])) {

            // Crear algunos tipos de programa en la base de datos
            $uid1 = (string) Str::uuid();
            $uid2 = (string) Str::uuid();

            // Insertar tipos de programa
            EducationalProgramTypesModel::insert([
                'uid' => $uid1, // Usar el UID generado
                'name' => 'Tipo 1',
            ]);

            EducationalProgramTypesModel::insert([
                'uid' => $uid2, // Usar el segundo UID generado
                'name' => 'Tipo 2',
            ]);

            // Datos de la convocatoria
            $data = [
                'call_uid' => null, // Para crear una nueva convocatoria
                'name' => "Convocatoria de Prueba",
                'start_date' => '2024-08-06 14:30',
                'end_date' => '2024-08-31 14:30',
                'program_types' => [$uid1, $uid2], // Usar los UIDs generados
            ];
            //dd($data);
            // Realizar la solicitud POST

            $response = $this->postJson('/management/calls/save_call', $data, [
                'Content-Type' => 'application/json',
            ]);
            //dd(CallsModel('start-date'));
            //dd($response);
            // Verificar la respuesta
            $response->assertStatus(200)
                ->assertJson(['message' => 'Convocatoria añadida correctamente']);

            // Verificar que la convocatoria fue creada en la base de datos
            $this->assertDatabaseHas('calls', [
                'uid' => $data['call_uid'], // Asegúrate de que esto sea correcto
                'name' => 'Convocatoria de Prueba',
                'start_date' => '2024-08-01',
                'end_date' => '2024-08-31',
            ]);

            // Verificar que los tipos de programa están asociados correctamente
            $call = CallsModel::where('uid', $data['call_uid'])->first();
            // Verificar que la convocatoria exista
            $this->assertNotNull($call, 'La convocatoria no fue encontrada.');

            // Verificar que la cantidad de tipos de programa asociados sea 2
            $this->assertCount(2, $call->educationalProgramTypes);

            // Verificar que los tipos de programa asociados contengan los UIDs generados
            $this->assertTrue($call->educationalProgramTypes->contains('uid', $uid1), 'El UID del Tipo 1 no está asociado a la convocatoria.');
            $this->assertTrue($call->educationalProgramTypes->contains('uid', $uid2), 'El UID del Tipo 2 no está asociado a la convocatoria.');
        }
    }
}
