<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\LogsController;
use App\Models\EducationalProgramsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrouselsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /** @test Guardar privisualización del Slider*/
    public function testSavesASliderPrevisualization()
    {
        // Simular los datos de la solicitud
        $data = [
            'title' => 'Sample Slider',
            'description' => 'This is a sample slider description.',
            'image' => UploadedFile::fake()->image('slider-image.jpg'),
            'color' => '#8a3838',
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/sliders/save_previsualization', $data);

        // Verificar que la respuesta sea correcta
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Se ha guardado la previsualización del slider',
            ]);

        // Verificar que el registro se haya guardado en la base de datos
        $this->assertDatabaseHas('sliders_previsualizations', [
            'title' => 'Sample Slider',
            'description' => 'This is a sample slider description.',
        ]);

        // Verificar que se haya generado un UID
        $this->assertNotNull($response->json('previsualizationUid'));
    }

    // /** @test Validación preview de slider */
    // public function testFailsValidationWhenDataIsMissingSlider()
    // {
    //     // Enviar datos incompletos
    //     $response = $this->postJson('/sliders/save_previsualization', []);

    //     // Verificar que la respuesta sea un error de validación
    //     $response->assertStatus(422)
    //              ->assertJsonValidationErrors(['title', 'description', 'image','color']);
    // }

    /** @test Guardar Carrusel Grande Aprobado  */
    public function testSavesBigCarrouselApprovals()
    {

        //   Crear registros usando el factory
         $course1 = CoursesModel::factory()->create([
            'featured_big_carrousel_approved' => false,
         ]);
   

        $course2 = CoursesModel::factory()->create([
            'featured_big_carrousel_approved' => true,
        ]);
        
        // Mockear los datos de entrada
        $courses = [
            ['uid' => $course1->uid, 'checked' => true],
            ['uid' => $course2->uid, 'checked' => false],
        ];

        $education1 = EducationalProgramsModel::factory()->create([
            'featured_slider_approved' => false,
        ]);

        $education2 = EducationalProgramsModel::factory()->create([
            'featured_slider_approved' => true,
        ]);

        $educationalPrograms = [
            ['uid' => $education1->uid, 'checked' => true],
            ['uid' => $education2->uid, 'checked' => false],
        ];   

        // Mockear la autenticación
        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Enviar petición a la ruta
        $response = $this->post('/administration/carrousels/save_big_carrousels_approvals', [
            'courses' => $courses,
            'educationalPrograms' => $educationalPrograms,
        ]);

        // Verificar la respuesta
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Se han actualizado los cursos a mostrar en el carrousel grande'
        ]);

        // Verificar los cambios en la base de datos
        $this->assertDatabaseHas('courses', [
            'uid' => $course1->uid,
            'featured_big_carrousel_approved' => true,
        ]);

        $this->assertDatabaseHas('courses', [
            'uid' => $course2->uid,
            'featured_big_carrousel_approved' => false,
        ]);

        $this->assertDatabaseHas('educational_programs', [
            'uid' => $education1->uid,
            'featured_slider_approved' => true,
        ]);

        $this->assertDatabaseHas('educational_programs', [
            'uid' => $education2->uid,
            'featured_slider_approved' => false,
        ]);

        // Verificar que se creó un registro en los logs
        // $this->assertDatabaseHas('logs', [
        //     'action' => 'Actualizar carrouseles grandes',
        //     'module' => 'Administración carrouseles',
        //     'user_uid' => $user->uid,
        // ]);
    }

    /** @test */ 
    // Esto no está hecho validar que se envia la data con 422 cuando este vacio 
    // public function TestFailsValidationWhenDataIsMissingBigCarrouselApprovals()
    // {
    //     // Enviar datos incompletos
    //     $data = [
    //         'courses' => [], // Sin cursos
    //         'educationalPrograms' => [], // Sin programas educativos
    //     ];

    //     $response = $this->postJson('/administration/carrousels/save_big_carrousels_approvals', $data);

    //     // Verificar que la respuesta sea un error de validación
    //     $response->assertStatus(422)
    //         ->assertJsonValidationErrors(['courses', 'educationalPrograms']);
    // }

     /** @test Guardar Carrusel Grande Aprobado  */
     public function testSavesSmallCarrouselApprovals()
     {
 
         //   Crear registros usando el factory
          $course1 = CoursesModel::factory()->create([
             'featured_small_carrousel_approved' => false,
          ]);
    
 
         $course2 = CoursesModel::factory()->create([
             'featured_small_carrousel_approved' => true,
         ]);
         
         // Mockear los datos de entrada
         $courses = [
             ['uid' => $course1->uid, 'checked' => true],
             ['uid' => $course2->uid, 'checked' => false],
         ];
 
         $education1 = EducationalProgramsModel::factory()->create([
             'featured_main_carrousel_approved' => false,
         ]);
 
         $education2 = EducationalProgramsModel::factory()->create([
             'featured_main_carrousel_approved' => true,
         ]);
 
         $educationalPrograms = [
             ['uid' => $education1->uid, 'checked' => true],
             ['uid' => $education2->uid, 'checked' => false],
         ];   
 
         // Mockear la autenticación
         $user = UsersModel::factory()->create();
         Auth::shouldReceive('user')->andReturn($user);
 
         // Enviar petición a la ruta
         $response = $this->post('/administration/carrousels/save_small_carrousels_approvals', [
             'courses' => $courses,
             'educationalPrograms' => $educationalPrograms,
         ]);
 
         // Verificar la respuesta
         $response->assertStatus(200);
         $response->assertJson([
             'status' => 'success',
             'message' => 'Se han actualizado los cursos a mostrar en el carrousel pequeño'
         ]);
 
         // Verificar los cambios en la base de datos
         $this->assertDatabaseHas('courses', [
             'uid' => $course1->uid,
             'featured_small_carrousel_approved' => true,
         ]);
 
         $this->assertDatabaseHas('courses', [
             'uid' => $course2->uid,
             'featured_small_carrousel_approved' => false,
         ]);
 
         $this->assertDatabaseHas('educational_programs', [
             'uid' => $education1->uid,
             'featured_main_carrousel_approved' => true,
         ]);
 
         $this->assertDatabaseHas('educational_programs', [
             'uid' => $education2->uid,
             'featured_main_carrousel_approved' => false,
         ]);
 
         // Verificar que se creó un registro en los logs
         // $this->assertDatabaseHas('logs', [
         //     'action' => 'Actualizar carrouseles grandes',
         //     'module' => 'Administración carrouseles',
         //     'user_uid' => $user->uid,
         // ]);
     }
}
