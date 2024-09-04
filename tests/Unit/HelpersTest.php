<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HelpersTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar el sistema de archivos para usar el disco en memoria
        Storage::fake('local');
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

     /** @test Formato datetime*/
    public function testFormatDateTime()
    {
        // Prueba con una fecha y hora de ejemplo
        $datetime = '2024-08-23 15:30:00';
        $formattedDate = formatDateTime($datetime);

        // Verifica el formato correcto
        $this->assertEquals('23/08/2024 a las 15:30', $formattedDate);
    }

     /** @test Encontrar Array*/
    public function testFindOneInArray()
    {
        $array = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ];

        // Buscar un elemento con id = 2
        $result = findOneInArray($array, 'id', 2);
        $this->assertNotNull($result);
        $this->assertEquals(['id' => 2, 'name' => 'Bob'], $result);

        // Buscar un elemento que no existe
        $result = findOneInArray($array, 'id', 999);
        $this->assertNull($result);
    }

     /** @test Encontrar un array de objeto*/
    public function testFindOneInArrayOfObjects()
    {
        // Crear una serie de objetos de prueba
        $object1 = (object) ['id' => 1, 'name' => 'Alice'];
        $object2 = (object) ['id' => 2, 'name' => 'Bob'];
        $object3 = (object) ['id' => 3, 'name' => 'Charlie'];

        $arrayOfObjects = [$object1, $object2, $object3];

        // Buscar un objeto con id = 2
        $result = findOneInArrayOfObjects($arrayOfObjects, 'id', 2);
        $this->assertNotNull($result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('Bob', $result->name);

        // Buscar un objeto que no existe
        $result = findOneInArrayOfObjects($arrayOfObjects, 'id', 999);
        $this->assertNull($result);
    }

         /** @test Chequea extensión de archivo*/
    public function testCheckFileExtension()
    {
        // Crear un archivo simulado
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Verificar extensión correcta
        $result = checkFileExtension($file, 'pdf');
        $this->assertTrue($result);

        // Verificar extensión incorrecta
        $result = checkFileExtension($file, 'jpg');
        $this->assertFalse($result);

        // Verificar caso sin archivo
        $result = checkFileExtension(null, 'pdf');
        $this->assertFalse($result);

        // Verificar caso sin extensión
        $fileWithoutExtension = UploadedFile::fake()->create('document', 100);
        $result = checkFileExtension($fileWithoutExtension, 'pdf');
        $this->assertFalse($result);
    }

     /** @test Notificación de formato datetime*/
    public function testFormatDateTimeNotifications()
    {
        // Fecha actual
        $now = Carbon::now();
        $result = formatDateTimeNotifications($now->toDateTimeString());
        $this->assertStringStartsWith('Hace', $result);

        // Fecha dentro de la semana pasada
        $lastWeek = Carbon::now()->subDays(3);
        $result = formatDateTimeNotifications($lastWeek->toDateTimeString());
        $this->assertStringContainsString('a las', $result);

        // Fecha más antigua que la semana pasada
        $olderDate = Carbon::now()->subMonths(2);
        $result = formatDateTimeNotifications($olderDate->toDateTimeString());
        $this->assertEquals($olderDate->format('d/m/Y') . ' a las ' . $olderDate->format('H:i'), $result);
    }

     /** @test AgregarTimestamp*/
    public function testAddTimestampNameFile()
    {
        // Crear un archivo simulado con Mockery
        $file = Mockery::mock('Illuminate\Http\UploadedFile');

        // Configurar el nombre original y la extensión del archivo
        $file->shouldReceive('getClientOriginalName')
            ->once()
            ->andReturn('document');
        $file->shouldReceive('getClientOriginalExtension')
            ->once()
            ->andReturn('pdf');

        // Capturar el timestamp actual
        $currentTimestamp = time();

        // Llamar a la función con el archivo simulado
        $result = add_timestamp_name_file($file);

        // Verificar que el resultado tenga el formato esperado
        $this->assertMatchesRegularExpression('/^document-' . $currentTimestamp . '\.pdf$/', $result);
    }

     /** @test Formato de datetime Usuario*/
    public function testFormatDatetimeUser()
    {
        // Define a sample date and expected result
        $datetime = '2024-08-23 14:45:00';
        $expected = '23 de agosto de 2024 a las 14:45';

        // Call the function to format the datetime
        $result = formatDatetimeUser($datetime);

        // Assert that the result matches the expected format
        $this->assertEquals($expected, $result);
    }

     /** @test SAnitize Filename*/
    public function testSanitizeFilename()
    {
        // Casos de prueba con nombres de archivo variados
        $testCases = [
            // Nombre de archivo con espacios y puntos
            ['file name with spaces and. dots.txt', 'filenamewithspacesanddotstxt'],

            // Nombre de archivo con paréntesis y caracteres especiales
            ['file(name)with*special?chars!.txt', 'filenamewithspecialcharstxt'],

            // Nombre de archivo con guiones que deben mantenerse
            ['file-name-with-dashes.txt', 'file-name-with-dashestxt'],

            // Nombre de archivo ya limpio
            ['cleanfilename.txt', 'cleanfilenametxt'],

            // Nombre de archivo vacío
            ['', '']
        ];

        foreach ($testCases as $case) {
            [$input, $expected] = $case;
            $result = sanitizeFilename($input);
            $this->assertEquals($expected, $result);
        }


    }

}
