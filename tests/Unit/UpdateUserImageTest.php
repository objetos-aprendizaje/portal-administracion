<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\ApiKeysModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateUserImageTest extends TestCase
{
    /**
     * @test Verifica que la imagen del usuario se sube correctamente.
     */
    public function testUserImageIsUploadedSuccessfully()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Simular la creación del archivo en el sistema de archivos público
        Storage::fake('public');

        // Crear un archivo simulado para la prueba
        $file = UploadedFile::fake()->image('user-image.jpg');

        // Hacer la solicitud POST a la ruta que maneja la subida de imágenes
        $response = $this->post(
            '/webhook/update_user_image',
            ['file' => $file],
            ['API-KEY' => $apikey->api_key]
        );

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la estructura JSON devuelta tiene la clave 'photo_path'
        $response->assertJsonStructure(['photo_path']);

        // Obtener la ruta del archivo desde la respuesta JSON
        $filePath = $response->json('photo_path');

        // Corregir la construcción de la ruta completa del archivo en el sistema de archivos
        $fullFilePath = public_path($filePath);

        // Verificar que el archivo se ha guardado en la ruta esperada usando el sistema de archivos real
        $this->assertTrue(file_exists($fullFilePath), "El archivo {$fullFilePath} no existe en el almacenamiento.");

        // Eliminar el archivo de prueba después de la verificación
        if (file_exists($fullFilePath)) {
            unlink($fullFilePath);
        }
    }
}
