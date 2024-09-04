<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\ApiKeysModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\BackendFileDownloadTokensModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * @test Verifica que el archivo se sube correctamente y se guarda en la ruta esperada.
     */
    public function testUploadFileSuccessfully()
    {
        // Crea un usuario y actúa como él
        $admin = UsersModel::factory()->create();
        $this->actingAs($admin);

        // Datos de para genera la key de la api 
        $apikey = ApiKeysModel::factory()->create()->first();

        $this->assertDatabaseHas('api_keys', ['uid' => $apikey->uid]);

        // Crear un archivo simulado para subir
        $file = UploadedFile::fake()->image('test-file.jpg');

        // Realizar la solicitud POST para subir el archivo
        $response = $this->postJson('/api/upload_file', ['file' => $file], [
            'API-KEY' => $apikey->api_key
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la respuesta contiene la ruta del archivo
        $response->assertJsonStructure(['file_path']);

        // Obtener la ruta del archivo desde la respuesta
        $filePath = $response->json('file_path');

        /// Corregie la construcción de la ruta completa del archivo en el sistema de archivos
        $fullFilePath = storage_path('app' . str_replace('/app', '', $filePath));

        // Verificar que el archivo se ha guardado en la ruta esperada usando el sistema de archivos de Laravel
        $this->assertTrue(file_exists($fullFilePath));
    }

    /**
     * @test Verifica que el archivo se descarga correctamente utilizando un token.
     */
    public function testDownloadFileTokenSuccessfully()
    {
        // Crear un archivo simulado en el sistema de almacenamiento
        $filePath = 'app/files/test-file.txt';
        Storage::disk('local')->put($filePath, 'Contenido de prueba');

        // Crear un token en la base de datos que apunte al archivo
        $token = Str::random(40);
        $backendFileDownloadToken = BackendFileDownloadTokensModel::factory()->create([
            'token' => $token,
            'file' => $filePath,
        ]);

        // Realizar la solicitud POST para descargar el archivo usando el token
        $response = $this->postJson('/download_file_token', [
            'token' => $token,
        ]);

        // Verificar que la respuesta sea 200 (OK)
        $response->assertStatus(200);

        // Verificar que la cabecera de la respuesta contenga el nombre correcto del archivo
        $response->assertHeader('Content-Disposition', 'attachment; filename=test-file.txt');

        // Verificar que el token se haya eliminado de la base de datos
        $this->assertDatabaseMissing('backend_file_download_tokens', ['token' => $token]);

        // Verificar que el archivo sigue existiendo en la ruta esperada
        $this->assertTrue(Storage::disk('local')->exists($filePath));

        // Eliminar el archivo después de la prueba
        Storage::disk('local')->delete($filePath);
    }
}
