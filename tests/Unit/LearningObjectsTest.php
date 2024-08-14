<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CourseTypesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;


class LearningObjectsTest extends TestCase
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
 * @testdox Verifica Duplicar Cursos*/
//     public function testDuplicateCourse()
//     {
//         $admin = UsersModel::factory()->create();
//         $roles_bd = UserRolesModel::get()->pluck('uid');
//         $roles_to_sync = [];
//         foreach ($roles_bd as $rol_uid) {
//             $roles_to_sync[] = [
//                 'uid' => generate_uuid(),
//                 'user_uid' => $admin->uid,
//                 'user_role_uid' => $rol_uid
//             ];
//         }

//         $admin->roles()->sync($roles_to_sync);
//         $this->actingAs($admin);
//         if ($admin->hasAnyRole(['ADMINISTRATOR'])) {


//         // Datos de prueba

//         // Crear un estado de curso para la duplicación
//         $courseStatus= new courseStatusesModel();
//         $courseStatus->uid = '555-12499-123456-000000-99999'; // Asigno el uid manualmente
//         $courseStatus->name = 'Status';
//         $courseStatus->code = 'DEVELOPMENT';
//         $courseStatus->save();
//         $courseStatus = CourseStatusesModel::find('555-12499-123456-000000-99999');

//         // Crea manual un tipo de curso
//         $courseType= new CourseTypesModel();
//         $courseType->uid = '989-12499-123456-000000-99999'; // Asigno el uid manualmente
//         $courseType->name = 'Curso';
//         $courseType->save();
//         $courseType = CourseTypesModel::find('989-12499-123456-000000-99999');

//         // Crear un curso existente en la base de datos
//         $course = CoursesModel::factory()->create([
//             'uid' => Str::uuid(),
//             'title' => 'Curso de prueba',
//             'course_status_uid' => $courseStatus->uid,
//             'course_type_uid' => $courseType->uid, // Asegúrate de que este también esté definido
//             'ects_workload' => 10,
//             'belongs_to_educational_program' => false,
//             'identifier' => 'identifier',
//         ]);
//         // Llama al método de duplicación
//         $response = $this->postJson('/learning_objects/courses/duplicate_course/'.$course->uid);


//         // Verificar que la respuesta sea correcta
//         $response->assertStatus(200);
//         $response->assertJson(['message' => 'Curso duplicado correctamente']);

//         // Verificar que el nuevo curso se haya creado
//         $newCourse = CoursesModel::where('title', $course->title . ' (copia)')->first();
//         $this->assertNotNull($newCourse);
//         $this->assertEquals($course->title . ' (copia)', $newCourse->title);
//         $this->assertEquals($admin->uid, $newCourse->creator_user_uid);
//         }
//     }

// /**
//  * @testdox Verifica función de nueva edición*/
//     public function testFunctionNewEdition()
//     {
//         $admin = UsersModel::factory()->create();
//         $roles_bd = UserRolesModel::get()->pluck('uid');
//         $roles_to_sync = [];
//         foreach ($roles_bd as $rol_uid) {
//             $roles_to_sync[] = [
//                 'uid' => generate_uuid(),
//                 'user_uid' => $admin->uid,
//                 'user_role_uid' => $rol_uid
//             ];
//         }

//         $admin->roles()->sync($roles_to_sync);
//         $this->actingAs($admin);
//         if ($admin->hasAnyRole(['ADMINISTRATOR'])) {


//         // Datos de prueba

//         // Crear un estado de curso para la duplicación
//         $courseStatus= new courseStatusesModel();
//         $courseStatus->uid = '555-12499-123456-000000-99999'; // Asigno el uid manualmente
//         $courseStatus->name = 'Status';
//         $courseStatus->code = 'DEVELOPMENT';
//         $courseStatus->save();
//         $courseStatus = CourseStatusesModel::find('555-12499-123456-000000-99999');

//         // Crea manual un tipo de curso
//         $courseType= new CourseTypesModel();
//         $courseType->uid = '989-12499-123456-000000-99999'; // Asigno el uid manualmente
//         $courseType->name = 'Curso';
//         $courseType->save();
//         $courseType = CourseTypesModel::find('989-12499-123456-000000-99999');

//         // Crear un curso existente en la base de datos
//         $course = CoursesModel::factory()->create([
//             'uid' => Str::uuid(),
//             'title' => 'Curso de prueba',
//             'course_status_uid' => $courseStatus->uid,
//             'course_type_uid' => $courseType->uid, // Asegúrate de que este también esté definido
//             'ects_workload' => 10,
//             'belongs_to_educational_program' => false,
//             'identifier' => 'identifier',
//         ]);
//         // Llama al método de duplicación
//         $response = $this->postJson('/learning_objects/courses/duplicate_course/'.$course->uid);


//         // Verificar que la respuesta sea correcta
//         $response->assertStatus(200);
//         $response->assertJson(['message' => 'Curso duplicado correctamente']);

//         // Verificar que el nuevo curso se haya creado
//         $newCourse = CoursesModel::where('title', $course->title . ' (copia)')->first();
//         $this->assertNotNull($newCourse);
//         $this->assertEquals($course->title . ' (copia)', $newCourse->title);
//         $this->assertEquals($admin->uid, $newCourse->creator_user_uid);
//         }
//     }
}

