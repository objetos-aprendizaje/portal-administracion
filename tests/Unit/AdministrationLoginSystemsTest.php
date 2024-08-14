<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\Saml2TenantsModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdministrationLoginSystemsTest extends TestCase
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

/**@group redes API */
/** @test  Submit Google */
    public function testSubmitGoogleFormWithValidData()
    {

        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        $response = $this->postJson(route('google-login'), [
            'google_login_active' => true,
            'google_client_id' => 'valid-client-id',
            'google_client_secret' => 'valid-client-secret',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Login de Google guardado correctamente']);

        // Check if the data is saved in the database
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'google_login_active',
            'option_value' => true,
        ]);
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'google_client_id',
            'option_value' => 'valid-client-id',
        ]);
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'google_client_secret',
            'option_value' => 'valid-client-secret',
        ]);
    }

/** @test  Submit Google Invalido*/
    public function testSubmitGoogleFormWithInvalidData()
    {
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        $response = $this->postJson(route('facebook-login'), [
            'facebook_login_active' => 1, // Use 1 instead of true
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors']);
    }

/** @test  Submit Google Inactivo*/
    public function testSubmitGoogleFormWhenInactive()
    {
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);
        $response = $this->postJson(route('google-login'), [
            'google_login_active' => false,
            // No need for client ID and secret if inactive
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Login de Google guardado correctamente']);
    }

/** @test  Submit Twitter */
    public function testSubmitTwitterFormWithValidData()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('twitter-login'), [
            'twitter_login_active' => true,
            'twitter_client_id' => 'valid-twitter-client-id',
            'twitter_client_secret' => 'valid-twitter-client-secret',
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de Twitter guardado correctamente']);

        // Check if the data is saved in the database
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'twitter_login_active',
            'option_value' => true,
        ]);
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'twitter_client_id',
            'option_value' => 'valid-twitter-client-id',
        ]);
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'twitter_client_secret',
            'option_value' => 'valid-twitter-client-secret',
        ]);
    }

/** @test  Submit Twitter  Invalido*/
    public function testSubmitTwitterFormWithInvalidData()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('twitter-login'), [
            'twitter_login_active' => 1,
            // Missing twitter_client_id and twitter_client_secret
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['message', 'errors']);
    }

/** @test  Submit Twitter Inactivo*/
    public function testSubmitTwitterFormWhenInactive()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('twitter-login'), [
            'twitter_login_active' => false,
            // No need for client ID and secret if inactive
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de Twitter guardado correctamente']);
    }


/** @test  Submit Linkedin */
    public function testSubmitLinkedinFormWithValidData()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('linkedin-login'), [
            'linkedin_login_active' => true,
            'linkedin_client_id' => 'valid-linkedin-client-id',
            'linkedin_client_secret' => 'valid-linkedin-client-secret',
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de Linkedin guardado correctamente']);

        // Check if the data is saved in the database
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'linkedin_login_active',
            'option_value' => true,
        ]);
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'linkedin_client_id',
            'option_value' => 'valid-linkedin-client-id',
        ]);
        $this->assertDatabaseHas('general_options', [
            'option_name' => 'linkedin_client_secret',
            'option_value' => 'valid-linkedin-client-secret',
        ]);
    }

/** @test  Submit Linkedin Invalido*/
    public function testSubmitLinkedinFormWithInvalidData()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('linkedin-login'), [
            'linkedin_login_active' => 1,

        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['message', 'errors']);
    }

/** @test  Submit Linkedin Inactivo*/
    public function testSubmitLinkedinFormWhenInactive()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('linkedin-login'), [
            'linkedin_login_active' => false,
            // No need for client ID and secret if inactive
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de Linkedin guardado correctamente']);
    }

/** @test  Submit Cas */
    public function testSubmitCasFormWithValidData()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('cas-login'), [
            'cas_entity_id' => 'valid-entity-id',
            'cas_login_url' => 'https://example.com/cas/login',
            'cas_logout_url' => 'https://example.com/cas/logout',
            'cas_certificate' => 'valid-certificate',
            'cas_login_active' => true,
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de CAS guardado correctamente']);

        // Check if the data is saved in the database
        $this->assertDatabaseHas('saml2_tenants', [
            'key' => 'cas',
            'idp_entity_id' => 'valid-entity-id',
            'idp_login_url' => 'https://example.com/cas/login',
            'idp_logout_url' => 'https://example.com/cas/logout',
            'idp_x509_cert' => 'valid-certificate',
        ]);
    }

/** @test  Submit Cas Invalido*/
    public function testSubmitCasFormWithInvalidData()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('cas-login'), [
            // Missing required fields
            'cas_login_active' => 1,
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['message', 'errors']);
    }

/** @test  Submit Cas con Cas existente*/
    public function testSubmitCasFormWithExistingCas()
    {
        // Create a user and authenticate
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // First, create a CAS entry to update
        Saml2TenantsModel::create([
            'uuid' => generate_uuid(),
            'key' => 'cas',
            'idp_entity_id' => 'existing-entity-id',
            'idp_login_url' => 'https://example.com/cas/login',
            'idp_logout_url' => 'https://example.com/cas/logout',
            'idp_x509_cert' => 'existing-certificate',
            'metadata' => '[]',
            'name_id_format' => 'persistent',
        ]);

        // Now test updating the existing CAS entry
        $response = $this->postJson(route('cas-login'), [
            'cas_entity_id' => 'updated-entity-id',
            'cas_login_url' => 'https://example.com/cas/login/updated',
            'cas_logout_url' => 'https://example.com/cas/logout/updated',
            'cas_certificate' => 'updated-certificate',
            'cas_login_active' => true,
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de CAS guardado correctamente']);

        // Check if the data is updated in the database
        $this->assertDatabaseHas('saml2_tenants', [
            'key' => 'cas',
            'idp_entity_id' => 'updated-entity-id',
            'idp_login_url' => 'https://example.com/cas/login/updated',
            'idp_logout_url' => 'https://example.com/cas/logout/updated',
            'idp_x509_cert' => 'updated-certificate',
        ]);
    }


/** @test  Submit Cas Inactivo*/
    public function testSubmitCasFormWhenInactive()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('cas-login'), [
            'cas_entity_id' => 'valid-entity-id',
            'cas_login_url' => 'https://example.com/cas/login',
            'cas_logout_url' => 'https://example.com/cas/logout',
            'cas_certificate' => 'valid-certificate',
            'cas_login_active' => false, // CAS login inactive
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de CAS guardado correctamente']);
    }


/** @test  Submit Rediris */
    public function testSubmitRedirisFormWithValidData()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('rediris-login'), [
            'rediris_entity_id' => 'valid-entity-id',
            'rediris_login_url' => 'https://example.com/rediris/login',
            'rediris_logout_url' => 'https://example.com/rediris/logout',
            'rediris_certificate' => 'valid-certificate',
            'rediris_login_active' => true,
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de REDIRIS guardado correctamente']);

        // Check if the data is saved in the database
        $this->assertDatabaseHas('saml2_tenants', [
            'key' => 'rediris',
            'idp_entity_id' => 'valid-entity-id',
            'idp_login_url' => 'https://example.com/rediris/login',
            'idp_logout_url' => 'https://example.com/rediris/logout',
            'idp_x509_cert' => 'valid-certificate',
        ]);
    }

/** @test  Submit Rediris Invalido */
    public function testSubmitRedirisFormWithInvalidData()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('rediris-login'), [

            'rediris_login_active' => 1,
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['message', 'errors']);
    }

/** @test  Submit Rediris Inactivo */
    public function testSubmitRedirisFormWhenInactive()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('rediris-login'), [
            'rediris_entity_id' => 'valid-entity-id',
            'rediris_login_url' => 'https://example.com/rediris/login',
            'rediris_logout_url' => 'https://example.com/rediris/logout',
            'rediris_certificate' => 'valid-certificate',
            'rediris_login_active' => false, // REDIRIS login inactive
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de REDIRIS guardado correctamente']);
    }

/** @test  Submit Rediris existente */
    public function testSubmitRedirisFormWithExistingRediris()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // First, create a REDIRIS entry to update
        Saml2TenantsModel::create([
            'uuid' => generate_uuid(),
            'key' => 'rediris',
            'idp_entity_id' => 'existing-entity-id',
            'idp_login_url' => 'https://example.com/rediris/login',
            'idp_logout_url' => 'https://example.com/rediris/logout',
            'idp_x509_cert' => 'existing-certificate',
            'metadata' => '[]',
            'name_id_format' => 'persistent',
        ]);

        // Now test updating the existing REDIRIS entry
        $response = $this->postJson(route('rediris-login'), [
            'rediris_entity_id' => 'updated-entity-id',
            'rediris_login_url' => 'https://example.com/rediris/login/updated',
            'rediris_logout_url' => 'https://example.com/rediris/logout/updated',
            'rediris_certificate' => 'updated-certificate',
            'rediris_login_active' => true,
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Login de REDIRIS guardado correctamente']);

        // Check if the data is updated in the database
        $this->assertDatabaseHas('saml2_tenants', [
            'key' => 'rediris',
            'idp_entity_id' => 'updated-entity-id',
            'idp_login_url' => 'https://example.com/rediris/login/updated',
            'idp_logout_url' => 'https://example.com/rediris/logout/updated',
            'idp_x509_cert' => 'updated-certificate',
        ]);
    }


/** Group Openai
 * @test  Guardar Openai */


    public function testSaveOpenaiForm()
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
            $openAiKey = 'fake_openai_key';

            // Enviar la solicitud POST
            $response = $this->postJson('/administration/save_openai_form', [
                'openai_key' => $openAiKey
            ]);

            // Verificar la respuesta
            $response->assertStatus(200)
                    ->assertJson(['message' => 'Clave de OpenAI guardada correctamente']);

            // Verificar que el valor se haya actualizado en la base de datos
            $this->assertDatabaseHas('general_options', [
                'option_name' => 'openai_key',
                'option_value' => $openAiKey
            ]);
        }
    }

}
