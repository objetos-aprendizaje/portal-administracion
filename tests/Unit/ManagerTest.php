<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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




class ManagerTest extends TestCase {
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

    public function testCreateCalls(){

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
                'name' => 'Tipo 10',
            ]);

            EducationalProgramTypesModel::insert([
                'uid' => $uid2, // Usar el segundo UID generado
                'name' => 'Tipo 20',
            ]);

            // Datos de la convocatoria
            $data = [
                'call_uid' => null, // Para crear una nueva convocatoria
                'name' => "Convocatoria de Prueba",
                'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
                'program_types' => [$uid1, $uid2], // Usar los UIDs generados
            ];

            // Crea una convocatoria existente
            CallsModel::insert([
            'uid' => '24ce6bf8-4f8e-11ef-a5e0-b0a4608c1236',
            'name' => 'Convocatoria de Prueba2',
            'description' => 'Descripción Original2',
            'start_date' =>  Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
            'end_date' =>  Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
            ]);

            $response = $this->postJson('/management/calls/save_call', $data, [
                'Content-Type' => 'application/json'
            ]);

            // Verificar la respuesta
            $response->assertStatus(200)
                ->assertJson(['message' => 'Convocatoria añadida correctamente']);

        }
    }


}


