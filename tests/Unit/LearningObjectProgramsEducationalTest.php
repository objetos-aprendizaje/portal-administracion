<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\CourseTypesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\EducationalProgramsModel;
use App\Exceptions\OperationFailedException;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Support\Facades\Notification;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\EducationalProgramsDocumentsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\EducationalProgramsStudentsDocumentsModel;
use App\Jobs\SendChangeStatusEducationalProgramNotification;
use App\Http\Controllers\LearningObjects\EducationalProgramsController;

class LearningObjectProgramsEducationalTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    } // Configuración inicial si es necesario

    /** @test Index Programa educativos */

    public function testIndexViewProgramEducational()
    {
        // Crear un usuario de prueba y asignar roles
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
       View::share('general_options', $general_options);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);

        // Crear datos de prueba
        CallsModel::factory()->count(3)->create();
        EducationalProgramTypesModel::factory()->count(2)->create();
        CategoriesModel::factory()->count(5)->create();

        // Realizar la solicitud a la ruta
        $response = $this->get(route('learning-objects-educational-programs'));

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertViewIs('learning_objects.educational_programs.index');
        $response->assertViewHas('page_name', 'Listado de programas formativos');
        $response->assertViewHas('calls');
        $response->assertViewHas('educational_program_types');
        $response->assertViewHas('categories');
        // $response->assertViewHas('variables_js', [
        //     'frontUrl' => env('FRONT_URL'),
        //     'rolesUser' => ['MANAGEMENT']
        // ]);
    }

    /** @test redirección Programa educativos */
    public function testRedirectionQueryProgramsEducational()
    {
        // Crear un usuario de prueba y asignar roles
        $user = UsersModel::factory()->create()->latest()->first();
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]);// Crea roles de prueba
        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        // Autenticar al usuario
        Auth::login($user);

        // Compartir la variable de roles manualmente con la vista
        View::share('roles', $roles);

        $general_options = GeneralOptionsModel::all()->pluck('option_value', 'option_name')->toArray();
       View::share('general_options', $general_options);

        // Simula datos de TooltipTextsModel
        $tooltip_texts = TooltipTextsModel::factory()->count(3)->create();
        View::share('tooltip_texts', $tooltip_texts);

        // Simula notificaciones no leídas
        $unread_notifications = $user->notifications->where('read_at', null);
        View::share('unread_notifications', $unread_notifications);

        // Simular la ruta
        $response = $this->get(route('redirection-queries-educational-program-types'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que la vista correcta fue cargada
        $response->assertViewIs('administration.redirection_queries_educational_program_types.index');

        // Verificar que los datos de la vista sean los correctos
        $response->assertViewHas('page_name', 'Redirección de consultas');
        $response->assertViewHas('page_title', 'Redirección de consultas');
        $response->assertViewHas('resources', [
            "resources/js/administration_module/redirection_queries_educational_program_types.js",
            "resources/js/modal_handler.js"
        ]);
        $response->assertViewHas('tabulator', true);
        $response->assertViewHas('submenuselected', 'redirection-queries-educational-program-types');
    }


     /** @test Elimina Programa educativos */
     public function TestDeleteEducationalPrograms()
     {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

         // Crear algunos programas educativos para eliminar
         $program1 = EducationalProgramsModel::factory()->withEducationalProgramType()->create();
         $program2 = EducationalProgramsModel::factory()->withEducationalProgramType()->create();

         // Asegurarse de que los programas existen en la base de datos
         $this->assertDatabaseHas('educational_programs', ['uid' => $program1->uid]);
         $this->assertDatabaseHas('educational_programs', ['uid' => $program2->uid]);

         // Enviar la solicitud DELETE
         $response = $this->delete('/learning_objects/educational_programs/delete_educational_programs', [
             'uids' => [$program1->uid, $program2->uid],
         ]);

         // Verificar la respuesta
         $response->assertStatus(200);
         $response->assertJson(['message' => 'Programas formativos eliminados correctamente']);

         // Asegurarse de que los programas han sido eliminados
         $this->assertDatabaseMissing('educational_programs', ['uid' => $program1->uid]);
         $this->assertDatabaseMissing('educational_programs', ['uid' => $program2->uid]);
     }


     /** @test */
    public function testSearchCoursesWithoutEducationalProgram()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);
        // Crear estados necesarios
        $statusReady = EducationalProgramStatusesModel::factory()->create([
            'uid' => generate_uuid(),
            'code' => 'READY_ADD_EDUCATIONAL_PROGRAM'
        ])->latest()->first();

        $typecourse1 = CourseTypesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'COURSE_TYPE_1',
        ])->latest()->first();
        ;

        $coursestatuses = CourseStatusesModel::factory()->create([
            'uid' => generate_uuid(),
            'code' => 'READY_ADD_EDUCATIONAL_PROGRAM',
        ])->latest()->first();


        // Crear cursos que cumplen con la búsqueda
        $course1 = CoursesModel::factory()->create([
            'uid' => generate_uuid(),
            'title' => 'Curso de Matemáticas',
            'description' => 'Descripción del curso de matemáticas',
            'course_type_uid' => $typecourse1->uid,
            'belongs_to_educational_program' => true,
            'course_status_uid' => $coursestatuses->uid,

        ]);

        $course2 = CoursesModel::factory()->create([
            'uid' => generate_uuid(),
            'title' => 'Curso de Matemáticas',
            'description' => 'Descripción del curso de matemáticas',
            'course_type_uid' => $typecourse1->uid,
            'belongs_to_educational_program' => true,
            'course_status_uid' => $coursestatuses->uid,
        ]);

        // Crear un curso que no debe ser incluido en la búsqueda
        $course3 = CoursesModel::factory()->create([
            'uid' => generate_uuid(),
            'title' => 'Curso de Matemáticas',
            'description' => 'Descripción del curso de matemáticas',
            'course_type_uid' => $typecourse1->uid,
            'belongs_to_educational_program' => true,
            'course_status_uid' => $coursestatuses->uid,
        ]);

        // Realizar la búsqueda
        $response = $this->get('/learning_objects/educational_programs/search_courses_without_educational_program/Matemáticas');

        // Verificar la respuesta
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Curso de Matemáticas']);
        $response->assertJsonMissing(['title' => 'Curso de Biología']);
    }

    /** @test Cambiar estatus de Programa educativo*/
    public function testChangeStatusesOfEducationalPrograms()
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

            $statusApproved = EducationalProgramStatusesModel::factory()->create(['code' => 'APPROVED']);
            $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
                'uid' => 'program-uid',
                'status_reason' => $statusApproved->uid,
            ]);

            // Mock del request
            $request = Request::create('/learning_objects/educational_programs/change_statuses_educational_programs', 'POST', [
                'changesEducationalProgramsStatuses' => [
                    ['uid' => 'program-uid', 'status' => 'APPROVED']
                ]
            ]);

            // Llamada al controlador
            $controller = new EducationalProgramsController();

            // Desactivamos notificaciones y trabajos para pruebas
            Notification::fake();
            Bus::fake();

            $response = $controller->changeStatusesEducationalPrograms($request);

            // Verificamos la respuesta
            $this->assertEquals(200, $response->status());
            $this->assertEquals('Se han actualizado los estados de los programas formativos correctamente', $response->getData()->message);

            // Verificamos que se haya actualizado el estado
            $this->assertEquals('APPROVED', $program->fresh()->status->code);

            // Verificamos que el trabajo de notificación fue despachado
            Bus::assertDispatched(SendChangeStatusEducationalProgramNotification::class);

        }
    }

    /** @test Cambiar estatus de Programa educativo sin autorización*/
    public function testChangeStatusesEducationalProgramsUnauthorized()
    {
        // Configuramos un usuario sin roles
        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);

        // Mock del request
        $request = Request::create('/learning_objects/educational_programs/change_statuses_educational_programs', 'POST', [
            'changesEducationalProgramsStatuses' => []
        ]);

        // Llamada al controlador
        $controller = new EducationalProgramsController();

        // Verificamos que se lanza la excepción de permisos
        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('No tienes permisos para realizar esta acción');

        $controller->changeStatusesEducationalPrograms($request);
    }

    /** @test Cambiar estatus de Programa educativo con data invalida*/
    public function testChangeStatusesEducationalProgramsInvalidData()
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

            // Mock del request con datos inválidos
            $request = Request::create('/learning_objects/educational_programs/change_statuses_educational_programs', 'POST', [
                'changesEducationalProgramsStatuses' => null
            ]);

            // Llamada al controlador
            $controller = new EducationalProgramsController();

            $response = $controller->changeStatusesEducationalPrograms($request);

            // Verificamos la respuesta
            $this->assertEquals(406, $response->status());
            $this->assertEquals('No se han enviado los datos correctamente', $response->getData()->message);
        }
    }

    /** @test Cambiar estatus de Programa educativo sin filtros*/
    public function testGetEducationalProgramStudentsWithoutFilters()
    {
            // Simulamos un programa educativo en la base de datos
            $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => generate_uuid()])->latest()->first();

            // Crear 5 estudiantes y asignarlos al programa
        $students = UsersModel::factory()->count(5)->create();
        $attachments = $students->mapWithKeys(function ($student) {
            return [$student->uid => ['uid' => (string) Str::uuid()]];
        });
        $program->students()->attach($attachments);

        // Simular la petición
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/'.$program->uid);

        // Verificar la respuesta
        $response->assertStatus(200);

        // Depurar la respuesta para ver los datos
        $data = $response->json('data');
        \Log::info('Response Data:', $data);
    }

    /** @test Cambiar estatus de Programa educativo con filtros de búsqueda*/
    public function testGetEducationalProgramStudentsWithSearchFilter()
    {
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => generate_uuid()])->latest()->first();

        // Crear usuarios y asignarles al programa
        $users = UsersModel::factory()->count(5)->create();
        $targetUser = $users->first();
        $targetUser->update(['first_name' => 'Julio', 'last_name' => 'Doe']);

        // Adjuntar cada usuario al programa con un uid único
        foreach ($users as $user) {
            $program->students()->attach($user->uid, [
                'uid' => generate_uuid(),
                'educational_program_uid' => $program->uid,
            ]);
        }

        // Simular la petición con un filtro de búsqueda
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/'.$program->uid.'?search=Julio');

        // Verificar la respuesta
        $response->assertStatus(200);

        // Depurar la respuesta para ver los datos
        $data = $response->json('data');
        \Log::info('Response Data:', $data);

        // Verificar que el usuario filtrado está presente en los resultados
        $this->assertTrue(
            collect($data)->contains(function ($student) use ($targetUser) {
                return $student['first_name'] === $targetUser->first_name &&
                       $student['last_name'] === $targetUser->last_name;
            })
        );
    }

    /** @test Cambiar estatus de Programa educativo Ordenado*/
    public function testGetEducationalProgramStudentsWithSorting()
    {
        // Crear un programa educativo
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => 'program-uid']);

        // Crear estudiantes y asignarlos al programa
        $studentA = UsersModel::factory()->create(['first_name' => 'Alice', 'last_name' => 'Zephyr']);
        $studentB = UsersModel::factory()->create(['first_name' => 'Bob', 'last_name' => 'Young']);
        $program->students()->attach($studentA->uid, ['uid' => (string) Str::uuid()]);
        $program->students()->attach($studentB->uid, ['uid' => (string) Str::uuid()]);

        // Simular la petición con ordenamiento
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/program-uid?sort[0][field]=first_name&sort[0][dir]=asc&size=10');

        // Verificar la respuesta
        $response->assertStatus(200);
        $this->assertEquals('Alice', $response->json('data.0.first_name'));
        $this->assertEquals('Bob', $response->json('data.1.first_name'));
    }

    /** @test Obtiene Programas educativos */
    public function testGetEducationalProgramStudentsWithPagination()
    {
        // Crear un programa educativo
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => 'program-uid']);

        // Crear estudiantes y asignarlos al programa
        $students = UsersModel::factory()->count(10)->create();
        $attachments = $students->mapWithKeys(function ($student) {
            return [$student->uid => ['uid' => (string) Str::uuid()]];
        });
        $program->students()->attach($attachments);

        // Simular la petición con paginación
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/program-uid?size=5');

        // Verificar la respuesta
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data')); // Verifica que solo se devuelven 5 estudiantes en la primera página
        $this->assertEquals(5, $response->json('per_page'));
    }

    /** @test Obtiene Estudiantes inscritos */
    public function testEnrollStudents()
    {

        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear un programa educativo
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => generate_uuid()])->latest()->first();

        // Crear usuarios para inscribir
        $users = UsersModel::factory()->count(3)->create();
        $userIds = $users->pluck('uid')->toArray();

        // Simular la petición
        $response = $this->postJson('/learning_objects/educational_program/enroll_students', [
            'EducationalProgramUid' => $program->uid,
            'usersToEnroll' => $userIds,
        ]);

        // Verificar la respuesta
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Alumnos añadidos al programa formativo',
        ]);

        // Verificar que los usuarios fueron inscritos correctamente
        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('educational_programs_students', [
                'educational_program_uid' => $program->uid,
                'user_uid' => $userId,
            ]);
        }

    }

     /** @test Estudiantes inscritos por CSV */
    public function testEnrollStudentsCsv()
    {
         // Crea un usuario autenticado para la prueba
         $this->actingAs(UsersModel::factory()->create());
         // Crea un programa educativo
         $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => generate_uuid()])->latest()->first();

         $programUid = $program->uid;

         // Crea dos usuarios y obtén sus datos
         UsersModel::factory()->create([
             'uid' => generate_uuid(),
             'first_name' => 'John',
             'last_name' => 'Doe',
             'nif' => '28632229N',
             'email' => 'john@example.com',
         ])->latest()->first();
         UsersModel::factory()->create([
             'uid' => generate_uuid(),
             'first_name' => 'Jane',
             'last_name' => 'Smith',
             'nif' => '79987901L',
             'email' => 'jane@example.com',
         ]);

         // Simula un archivo CSV
         Storage::fake('local');
         $csvContent = "first_name,last_name,nif,email\n" .
             "John,Doe,28632229N,john@example.com\n" .
             "Jane,Smith,79987901L,jane@example.com";
         $csvFile = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

         // Datos de solicitud
         $requestData = [
             'educational_program_uid' => $programUid,
             'attachment' => $csvFile,
         ];
         // Realiza la solicitud POST a la ruta
         $response = $this->postJson('/learning_objects/educational_program/enroll_students_csv', $requestData);

         // Verifica que la respuesta sea exitosa
         $response->assertStatus(200);
         // Verifica que el mensaje de respuesta sea el esperado
         $response->assertJson(['message' => 'Alumnos añadidos al programa formativo. Los ya registrados no se han añadido.']);
    }

     /** @test Edición Programa educacional */
    public function testEditionEducationalProgram()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        $status = EducationalProgramStatusesModel::create([
            'uid' => generate_uuid(),
            'code' => 'INTRODUCTION',
            'name' => 'Introducción'
        ])->latest()->first();

        // Crear un tipo de programa educativo
        $programType1 = EducationalProgramTypesModel::factory()->create()->first();

        // Crear un programa educativo existente
        $educationalProgram = EducationalProgramsModel::create([
            'uid' => generate_uuid(),
            'name' => 'Programa Original',
            'educational_program_status_uid' => $status->uid,
            'educational_program_type_uid' => $programType1->uid,
        ]);

        // Realizar la solicitud POST para edición
        $response = $this->postJson('/learning_objects/educational_program/edition_or_duplicate_educational_program', [
            'educationalProgramUid' => $educationalProgram->uid,
            'action' => 'edition',
        ]);

        // Verificar la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Edición creada correctamente']);

        // Verificar que se creó un nuevo programa educativo
        $this->assertDatabaseHas('educational_programs', [
            'name' => 'Programa Original (nueva edición)',
            'educational_program_origin_uid' => $educationalProgram->uid,
        ]);
    }

    /** @test Duplica Programa educacional */
    public function testDuplicationNewEducationalProgram()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        $status = EducationalProgramStatusesModel::create([
            'uid' => generate_uuid(),
            'code' => 'INTRODUCTION',
            'name' => 'Introducción'
        ])->latest()->first();
        // Crear un tipo de programa educativo
        $programType1 = EducationalProgramTypesModel::factory()->create()->first();

        // Crear un programa educativo existente
        $educationalProgram = EducationalProgramsModel::create([
            'uid' => 'existing-uid',
            'name' => 'Programa Original',
            'educational_program_status_uid' => $status->uid,
            'educational_program_type_uid' => $programType1->uid,

        ]);

        // Realizar la solicitud POST para duplicación
        $response = $this->postJson('/learning_objects/educational_program/edition_or_duplicate_educational_program', [
            'educationalProgramUid' => $educationalProgram->uid,
            'action' => 'duplication',
        ]);

        // Verificar la respuesta
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Programa duplicado correctamente']);

        // Verificar que se creó un nuevo programa educativo
        $this->assertDatabaseHas('educational_programs', [
            'name' => 'Programa Original (copia)',
        ]);
    }

    /** @test Download dcoumento */
    public function testDocumentDownloadStudentEducationalProgram()
    {
        // Crea un usuario autenticado
        $user = UsersModel::factory()->create()->first();
        $this->actingAs($user);

        $status = EducationalProgramStatusesModel::create([
            'uid' => generate_uuid(),
            'code' => 'INTRODUCTION',
            'name' => 'Introducción'
        ])->latest()->first();

        $programType1 = EducationalProgramTypesModel::factory()->create()->first();

        // Crear un programa educativo existente
        $educationalProgram = EducationalProgramsModel::create([
            'uid' => generate_uuid(),
            'name' => 'Programa Original',
            'educational_program_status_uid' => $status->uid,
            'educational_program_type_uid' => $programType1->uid,
        ]);

        $educationalprogramdocument = EducationalProgramsDocumentsModel::create([
            'uid' => generate_uuid(),
            'educational_program_uid' => $educationalProgram->uid,
            'document_name' => 'Documento Original',
        ])->latest()->first();

        // Verifica y crea la estructura de directorios si es necesario
        Storage::disk('public')->makeDirectory('documents');

        // Copia el archivo a la ubicación pública de almacenamiento
        $sourceFilePath = public_path('document.pdf');
        $targetFilePath = storage_path('app/public/documents/document.pdf');

        if (file_exists($sourceFilePath)) {
            // Asegúrate de que el archivo se copie a la ubicación correcta
            copy($sourceFilePath, $targetFilePath);
        } else {
            $this->fail('El archivo source public/document.pdf no existe.');
        }

        // Verifica que el archivo se haya copiado correctamente
        $this->assertFileExists($targetFilePath, 'El archivo no se copió correctamente a la ruta esperada.');

        // Crear un registro en la base de datos
        $document = EducationalProgramsStudentsDocumentsModel::create([
            'uid' => generate_uuid(),
            'user_uid' => $user->uid,
            'educational_program_document_uid' => $educationalprogramdocument->uid,
            'document_path' => 'documents/document.pdf',
        ]);

        // Realizar la solicitud POST para descargar el documento
        $response = $this->postJson('/learning_objects/educational_program/download_document_student', [
            'uidDocument' => $document->uid,
        ]);

         // Verifica que la respuesta sea exitosa y que se descarga el archivo correcto
        $response->assertStatus(200);
        $response->assertDownload('document.pdf');
    }
        protected function tearDown(): void
    {
        parent::tearDown();

        // Eliminar el archivo creado durante la prueba
        $targetFilePath = storage_path('app/public/document/document.pdf');
        if (file_exists($targetFilePath)) {
            unlink($targetFilePath);
        }
    }

    /** @test Elimina inscripción */
    public function testDeleteInscriptionsSuccess()
    {
        // Crea un usuario autenticado
        $user = UsersModel::factory()->create()->first();
        $this->actingAs($user);


        $status = EducationalProgramStatusesModel::create([
            'uid' => generate_uuid(),
            'code' => 'INTRODUCTION',
            'name' => 'Introducción'
        ])->latest()->first();

        $programType1 = EducationalProgramTypesModel::factory()->create()->first();

        // Crear un programa educativo existente
        $educationalProgram = EducationalProgramsModel::create([
            'uid' => generate_uuid(),
            'name' => 'Programa Original',
            'educational_program_status_uid' => $status->uid,
            'educational_program_type_uid' => $programType1->uid,
        ]);

        //Estudiante
        $student1 = UsersModel::factory()->create()->first();


        // Crear inscripciones
        $inscription1 = EducationalProgramsStudentsModel::create([
            'uid' => generate_uuid(),
            'user_uid' => $student1->uid,
            'educational_program_uid' => $educationalProgram->uid,
        ])->latest()->first();


        // Verificar que las inscripciones existan antes de la eliminación
        $this->assertDatabaseHas('educational_programs_students', [
            'uid' => $inscription1->uid,
        ]);

        // Realizar la solicitud DELETE para eliminar las inscripciones
        $response = $this->deleteJson('/learning_objects/educational_program/delete_inscriptions_educational_program', [
            'uids' => [$inscription1->uid],
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Inscripciones eliminadas correctamente']);

        // Verificar que las inscripciones ya no existan en la base de datos
        $this->assertDatabaseMissing('educational_programs_students', [
            'uid' => $inscription1->uid,
        ]);

    }

    // :::::::::::::::::::::::::::::: Esta parte pertenece al Modulo LearningObjectProgramsEducationalTest :::::::::::::::

    /** @test Obtener programas formativos sin filtros ni ordenamiento */
    public function testGetEducationalProgramsWithoutFiltersOrSorting()
    {
        // Crear un usuario sin rol de MANAGEMENT
        $user = UsersModel::factory()->create();
        $user->roles()->sync([
            UserRolesModel::where('code', 'TEACHER')->first()->uid => ['uid' => generate_uuid()]
        ]);

        // Crear los datos relacionados
        $programType = EducationalProgramTypesModel::factory()->create()->first();
        $call = CallsModel::factory()->create()->first();
        $status = EducationalProgramStatusesModel::factory()->create()->first();

        // Crear programas formativos
        EducationalProgramsModel::factory()->count(5)->create([
            'creator_user_uid' => $user->uid,
            'educational_program_type_uid' => $programType->uid,
            'call_uid' => $call->uid,
            'educational_program_status_uid' => $status->uid,
        ]);

        // Simular autenticación
        $this->actingAs($user);

        // Llamar al endpoint
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_programs');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uid',
                        'name',
                        'description',
                        'identifier',
                        'educational_program_type_name',
                        'call_name',
                        'status_name',
                        'status_code',
                    ]
                ],
                'links',
            ]);
    }


    /** @test Obtener programas formativos con búsqueda */
    public function testGetEducationalProgramsWithSearch()
    {
        $user = UsersModel::factory()->create();
        $user->roles()->sync([
            UserRolesModel::where('code', 'TEACHER')->first()->uid => ['uid' => generate_uuid()]
        ]);

        $programType = EducationalProgramTypesModel::factory()->create()->first();
        $call = CallsModel::factory()->create()->first();
        $status = EducationalProgramStatusesModel::factory()->create()->first();

        $program = EducationalProgramsModel::factory()->create([
            'creator_user_uid' => $user->uid,
            'educational_program_type_uid' => $programType->uid,
            'call_uid' => $call->uid,
            'educational_program_status_uid' => $status->uid,
            'name' => 'Unique Program Name',
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/learning_objects/educational_programs/get_educational_programs?search=Unique');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Unique Program Name']);
    }


    /** @test Obtener programas formativos con filtros y ordenamiento */
    public function testGetEducationalProgramsWithSorting()
    {
        $user = UsersModel::factory()->create();
        $user->roles()->sync([
            UserRolesModel::where('code', 'TEACHER')->first()->uid => ['uid' => generate_uuid()]
        ]);

        $programType = EducationalProgramTypesModel::factory()->create()->first();
        $call = CallsModel::factory()->create()->first();
        $status = EducationalProgramStatusesModel::factory()->create()->first();

        EducationalProgramsModel::factory()->create([
            'creator_user_uid' => $user->uid,
            'educational_program_type_uid' => $programType->uid,
            'call_uid' => $call->uid,
            'educational_program_status_uid' => $status->uid,
            'name' => 'A Program',
        ]);

        EducationalProgramsModel::factory()->create([
            'creator_user_uid' => $user->uid,
            'educational_program_type_uid' => $programType->uid,
            'call_uid' => $call->uid,
            'educational_program_status_uid' => $status->uid,
            'name' => 'B Program',
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/learning_objects/educational_programs/get_educational_programs?size=10&sort[0][field]=name&sort[0][dir]=asc&size=2');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Depuración: Imprimir el array data para revisar su estructura
        // dd($data);

        // Asegurarse de que existen al menos dos elementos en data
        $this->assertCount(2, $data, 'Expected at least 2 programs but got fewer.');

        $this->assertEquals('A Program', $data[0]['name']);
        $this->assertEquals('B Program', $data[1]['name']);
    }

    /** @test Obtener programas formativos como usuario con rol de MANAGEMENT */
    public function testGetEducationalProgramsAsManagementRole()
    {
        $user = UsersModel::factory()->create();
        $user->roles()->sync([
            UserRolesModel::where('code', 'MANAGEMENT')->first()->uid => ['uid' => generate_uuid()]
        ]);

        $programType = EducationalProgramTypesModel::factory()->create()->first();
        $call = CallsModel::factory()->create()->first();
        $status = EducationalProgramStatusesModel::factory()->create()->first();

        // Crear programas formativos de otro usuario
        $anotherUser = UsersModel::factory()->create();
        EducationalProgramsModel::factory()->create([
            'creator_user_uid' => $anotherUser->uid,
            'educational_program_type_uid' => $programType->uid,
            'call_uid' => $call->uid,
            'educational_program_status_uid' => $status->uid,
        ]);

        $this->actingAs($user);

        // Llamar al endpoint
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_programs');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uid',
                        'name',
                        'description',
                        'identifier',
                        'educational_program_type_name',
                        'call_name',
                        'status_name',
                        'status_code',
                    ]
                ],
                'links',
            ]);
    }


    /** @test Obtener un programa formativo con un UID válido */
    public function testGetEducationalProgramWithValidUid()
    {
        $user = UsersModel::factory()->create();

        // Crear un programa formativo
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create()->first();

        $this->actingAs($user);

        // Hacer la solicitud al endpoint con un UID válido
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program/' . $program->uid);

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJson([
                'uid' => $program->uid,
                'name' => $program->name,
                'description' => $program->description,
            ]);
    }

    /** @test Obtener un programa formativo con un UID inválido */
    public function testGetEducationalProgramWithInvalidUid()
    {
        $user = UsersModel::factory()->create();

        $this->actingAs($user);

        // Hacer la solicitud al endpoint con un UID inexistente
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program/' . 'invalid-uid');

        // Verificar la respuesta
        $response->assertStatus(406)
            ->assertJson([
                'message' => 'El programa formativo no existe',
            ]);
    }

    /**
     * @test Obtener todas las competencias de Tipo de programa Educacional
     */
    public function testGetAllCompetencesEducationalProgramType()
    {
        // Crear datos de prueba
        UsersModel::factory()->create();

        // Crear datos de prueba
        $competences = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Competencia 1',
            'description' => 'Descripción de la competencia 1',
            'type' => 'educational_program',
            'parent_competence_uid' => null
        ])->latest()->first();

        // Crear subcompetencias para la competencia principal
        $subcompetences = CompetencesModel::factory()->count(2)->create([
            'parent_competence_uid' => $competences->uid
        ]);

        $competences->subcompetences()->saveMany($subcompetences);

        // Realizar la solicitud a la ruta
        $response = $this->get('/learning_objects/educational_programs/get_educational_program_type');

        // Verificar que la respuesta es correcta
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'uid',
                'name',
                'description',
                'created_at',
                'updated_at',
                'subcompetences' => [
                    '*' => [
                        'uid',
                        'name',
                        'description',
                        'parent_competence_uid',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);
    }
    //:::::::::::::::::::::::::: Fin Modulo LearningObjectProgramsEducationalTest  :::::::::::::::::::::::::::::::::::::::

}




