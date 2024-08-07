<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use App\Models\UserRolesModel;
use PHPUnit\Framework\Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\NotificationsTypesModel;


class UsersTest extends TestCase
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
 * @testdox Crear Usuario Exitoso*/
    public function testCreateUser()
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
        $userRole = UserRolesModel::where('code', 'STUDENT')->first();
        $userRoleUid = $userRole->uid;

        // Datos de prueba
        $data = [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'nif' => '12345678A',
            'email' => 'juan.perez@example.com',
            'curriculum' => 'Curriculum content',
            'department_uid' => 'dept-123',
            'photo_path' => null,
            'roles' => json_encode([$userRoleUid]),
        ];

         $response = $this->postJson('/users/list_users/save_user', $data);


        // Verificar la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Se ha creado el usuario correctamente']);

        // Verificar que el usuario fue creado en la base de datos
        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@example.com',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
        ]);
    }



    }

 /**
 * @testdox Valida error campos obligatorios*/
    public function testValidateRequiredField(){

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
            // Datos de prueba con campos obligatorios faltantes
            $data = [
                'first_name' => '',
                'last_name' => '',
                'nif' => '12345678A',
                'email' => 'juan.perez@example.com',
                'curriculum' => 'Curriculum content',
                'department_uid' => 'dept-123',
                'photo_path' => UploadedFile::fake()->image('photo.jpg'),

            ];

            // Realiza la solicitud
            $response = $this->postJson('/users/list_users/save_user', $data);

            // Verifica que la respuesta tenga un estado de error
            $response->assertStatus(400);

            // Verifica que los errores de validación estén presentes
            $response->assertJsonValidationErrors(['first_name', 'last_name', 'roles']);
        }
    }

    public function testUpdateUser(){
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
        $userRole = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $userRoleUid = $userRole->uid;
        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {
            //datos a actualizar
            $data = [
                'user_uid' => $admin->uid,
                'first_name' => 'José',
                'last_name' => 'Duch',
                'nif' => '12345678A',
                'email' => 'jose.duch@example.com',
                'curriculum' => 'Updated curriculum content',
                'department_uid' => 'department-uuid',
                'photo_path' => UploadedFile::fake()->image('photo.jpg'),
                'roles' => json_encode([$userRoleUid]),
            ];

            // Realizar la solicitud POST para actualizar el usuario
            $response = $this->postJson('/users/list_users/save_user', $data);

            // Verificar que la respuesta sea correcta
            $response->assertStatus(200)
                     ->assertJson(['message' => 'Se ha actualizado el usuario correctamente']);

            // Verificar que el usuario se haya actualizado en la base de datos
            $this->assertDatabaseHas('users', [
                'uid' => $admin->uid,
                'email' => 'jose.duch@example.com',
                'first_name' => 'Jose',
                'last_name' => 'Duch',
            ]);
        }
    }

    public function testReturnsValidationEmail()
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

            // Datos de prueba con un email inválido
            $data = [
                'user_uid' => $admin->uid,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'nif' => '12345678A',
                'email' => 'invalid_email', // Email inválido
                'curriculum' => 'Updated curriculum content',
                'department_uid' => 'department-uuid',
                'roles' => json_encode(['role1', 'role2']),
            ];

            // Realizar la solicitud POST para actualizar el usuario
            $response = $this->postJson('/users/list_users/save_user', $data);

            // Verificar que se devuelva un error de validación para el email
            $response->assertStatus(400)
                ->assertJsonValidationErrors('email');
        }
    }

    public function testSaveUserPreference()
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
            NotificationsTypesModel::factory()->create();
            NotificationsTypesModel::factory()->create();
            if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            // Datos de prueba
            $data = [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'nif' => '21843185Q', // Asegúrate de que este NIF sea válido
            'curriculum' => 'Curriculum content',
            'department_uid' => 'dept-123',
            'photo_path' => null,
            'general_notifications_allowed' => true,
            'email_notifications_allowed' => false,
            'general_notification_types_disabled' => json_encode([]),
            'email_notification_types_disabled' => json_encode([]),
            'automatic_general_notification_types_disabled' => json_encode([]),
            'automatic_email_notification_types_disabled' => json_encode([])
        ];

            $response = $this->post('/my_profile/update', $data);

            $response->assertStatus(200);
            $response->assertJson(['message' => 'Tu perfil se ha actualizado correctamente']);

            $this->assertDatabaseHas('users', [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'nif' => '21843185Q',
                'curriculum' => 'Curriculum content',
                'department_uid' => 'dept-123',

            ]);
        }
    }

}
