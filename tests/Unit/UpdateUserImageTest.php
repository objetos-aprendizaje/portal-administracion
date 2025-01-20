<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use App\Models\UserRolesModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UpdateUserImageTest extends TestCase
{
    /**
     * @test Verifica que la imagen del usuario se sube correctamente.
     */
    public function testUserImageIsUploadedSuccessfully()
    {
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generateUuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generateUuid()]);

        // Autenticar al usuario
        Auth::login($user);
        if ($user->roles()->where('code', 'ADMINISTRATOR')->exists()) {

                // Desactivar manejo de excepciones para ver errores detallados
            $this->withoutExceptionHandling();

            // Simular la creación del sistema de archivos público
            Storage::fake('public');

            // Crear un archivo simulado para la prueba
            $file = UploadedFile::fake()->image('user-image.jpg');

            // Establecer temporalmente la API key para las pruebas
            config(['API_KEY_FRONT' => env('API_KEY_FRONT')]);

            // Hacer la solicitud POST a la ruta que maneja la subida de imágenes
            $response = $this->post('/webhook/update_user_image', [
                'file' => $file,
            ], [
                'API-KEY' => env('API_KEY_FRONT')
            ]);

            // Verificar que la respuesta sea 200 (OK)
            $response->assertStatus(200);

            // Verificar que la estructura JSON devuelta tiene la clave 'photo_path'
            $response->assertJsonStructure(['photo_path']);

        }
    }


}
