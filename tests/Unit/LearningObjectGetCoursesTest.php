<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LearningObjectGetCoursesTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }
     /** @test  Obtener cursos como Admainistrator*/
     public function testAllCoursesForAdministrator()
     {
         $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'ADMINISTRATOR'], ['uid' => generate_uuid()]);
         $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

         // Autenticar al usuario
         Auth::login($user);

         // Compartir la variable de roles manualmente con la vista
         View::share('roles', $roles);

         // Crear cursos de ejemplo
         CoursesModel::factory()->create();

         // Simular la solicitud
         $response = $this->postJson('/learning_objects/courses/get_courses');

         // Verificar que la respuesta sea exitosa
         $response->assertStatus(200);
         $this->assertCount(CoursesModel::count(), $response->json('data'));

     }

     /** @test */
     public function testCoursesForTeacher()
     {
         $teacher = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'TEACHER'], ['uid' => generate_uuid()]);
         $teacher->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

         // Autenticar al usuario
         Auth::login($teacher);

         // Compartir la variable de roles manualmente con la vista
         View::share('roles', $roles);

         // Crear cursos cerado por profesor
         CoursesModel::factory()->create(['creator_user_uid' => $teacher->uid]);
         // Crear cursos crado por un usuario diferente a profesor
         CoursesModel::factory()->create();

         // Suponiendo que el profesor está asignado a algunos cursos
         $course3 = CoursesModel::factory()->create(); // Curso creado por otro usuario
         $course3->teachers_coordinate()->attach($teacher->uid, ['uid' => generate_uuid()]); // Asignar al profesor como coordinador

         // Simular la solicitud
         $response = $this->postJson('/learning_objects/courses/get_courses');

         // Verificar que la respuesta sea exitosa
         $response->assertStatus(200);
     }

     /** @test */
     public function testCoursesBasedOnSearch()
     {

         $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
         $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

         // Autenticar al usuario
         Auth::login($user);

         // Crear cursos de ejemplo
         CoursesModel::factory()->create(['title' => 'Mathematics']);
         CoursesModel::factory()->create(['title' => 'Science']);
         CoursesModel::factory()->create(['title' => 'History']);


         // Simular la búsqueda de cursos
         $response = $this->postJson('/learning_objects/courses/get_courses?search=Math');

         // Verificar que la respuesta sea exitosa
         $response->assertStatus(200);
         // Asegúrate de que solo se devuelva el curso que coincide con la búsqueda
         $this->assertCount(1, $response->json('data'));
         $this->assertEquals('Mathematics', $response->json('data')[0]['title']);
     }

     /** @test */
     public function testSortsCoursesBasedOn()
     {

         $user = UsersModel::factory()->create()->latest()->first();
         $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);
         $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

         // Autenticar al usuario
         Auth::login($user);

         // Crear cursos de ejemplo
         CoursesModel::factory()->create(['title' => 'Science']);
         CoursesModel::factory()->create(['title' => 'Mathematics']);
         CoursesModel::factory()->create(['title' => 'History']);

         // Simular la solicitud con parámetros de ordenamiento
         $response = $this->postJson('/learning_objects/courses/get_courses?sort[0][field]=title&sort[0][dir]=asc&size=5');

         // Verificar que la respuesta sea exitosa
         $response->assertStatus(200);

         // Verificar que los cursos estén ordenados alfabéticamente
         $sortedData = $response->json('data');
         $this->assertEquals('History', $sortedData[0]['title']);
         $this->assertEquals('Mathematics', $sortedData[1]['title']);
         $this->assertEquals('Science', $sortedData[2]['title']);
     }
}
