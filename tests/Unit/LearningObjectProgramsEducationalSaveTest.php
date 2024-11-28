<?php

namespace Tests\Unit;

use Mockery;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\LmsSystemsModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\EducationalProgramEmailContactsModel;
use App\Models\EducationalProgramsPaymentTermsModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Http\Controllers\LearningObjects\EducationalProgramsController;

class LearningObjectProgramsEducationalSaveTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {

        parent::setUp();
        $this->withoutMiddleware();
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    }

    /**
     * @test
     * Verifica que un nuevo programa educativo se crea correctamente.
     */
    public function testCreatesANewEducationalProgram()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
            'certidigital_url'              => 'https://certidigital-k8s.atica.um.es',
            'certidigital_client_id'        => 'certidigi-admin',
            'certidigital_client_secret'    => 'aKli757XUHqVIDC9cu8iwIH4U64qvM7T',
            'certidigital_username'         => 'eadmon.umu@gmail.com',
            'certidigital_password'         => 'wEVZ3rDar10',
            'certidigital_url_token'        => 'https://certidigital-k8s.atica.um.es/realms/certidigi/protocol/openid-connect/token',
            'certidigital_center_id'        => 105,
            'certidigital_organization_oid' => 29,

        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        $lms = LmsSystemsModel::factory()->create();

        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create(
            [
                'lms_system_uid' => $lms->uid,
            ]
        );

        $course = CoursesModel::where('uid', $course->uid)->first();
        // dd($course); // Verifica que este curso exista

        // Datos de prueba
        $data = [
            'action' => 'submit',
            'courses' => [$course->uid],
            'name' => 'Programa Educativo de Prueba',
            'educational_program_type_uid' => $programType->uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 100,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];

        // dd($data);

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Programa formativo añadido correctamente']);

        // Verificar que el programa educativo se haya guardado en la base de datos
        $this->assertDatabaseHas('educational_programs', ['name' => 'Programa Educativo de Prueba']);


        // CUANDO ES DRAFT

        // Datos de prueba
        $data = [
            'action' => 'draft',
            'name' => 'Programa Educativo de Prueba',
            'educational_program_type_uid' => $programType->uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 100,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];
        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);


        // Cuando 'validate_student_registrations' = 0 y 'payment_mode' ='INSTALLMENT_PAYMENT',


        // Datos de prueba
        $data = [
            'action' => 'submit',
            'courses' => [$course->uid],
            'name' => 'Programa Educativo de Prueba',
            'educational_program_type_uid' => $programType->uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 0,
            'evaluation_criteria' => 'Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'cost' => 100,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            'payment_terms' => json_encode([
                [
                    'uid'         => generate_uuid(),
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => 100,
                ]
            ]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];
        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
    }


    //Todo: Falta rellenar algunas lineas de codigo mas 
    /**
     * @test
     * Verifica que un nuevo programa educativo se crea correctamente.
     */
    public function testCreatesANewEducationalProgramNotManagement()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);      


        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
            'certidigital_url'              => 'https://certidigital-k8s.atica.um.es',
            'certidigital_client_id'        => 'certidigi-admin',
            'certidigital_client_secret'    => 'aKli757XUHqVIDC9cu8iwIH4U64qvM7T',
            'certidigital_username'         => 'eadmon.umu@gmail.com',
            'certidigital_password'         => 'wEVZ3rDar10',
            'certidigital_url_token'        => 'https://certidigital-k8s.atica.um.es/realms/certidigi/protocol/openid-connect/token',
            'certidigital_center_id'        => 105,
            'certidigital_organization_oid' => 29,
        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        // Datos de prueba
        $data = [
            'action' => 'submit',
            'name' => 'Programa Educativo de Prueba',
            'educational_program_type_uid' => $programType->uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 100,
            'title'=>'Title',
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Programa formativo añadido correctamente']);

        // Verificar que el programa educativo se haya guardado en la base de datos
        $this->assertDatabaseHas('educational_programs', ['name' => 'Programa Educativo de Prueba']);


        //  Educational Program with educational_program_origin_uid        
        $educationalProgramExist = EducationalProgramsModel::factory()->withEducationalProgramType()->create();

        $status = EducationalProgramStatusesModel::where('code', 'INTRODUCTION')->first();


        $educational_program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(
            [
                'educational_program_origin_uid' => $educationalProgramExist->uid,
                'educational_program_status_uid' => $status->uid,

            ]
        );


        $educationPayment = EducationalProgramsPaymentTermsModel::factory()->create(
            [
                'educational_program_uid' => $educational_program->uid
            ]
        );

        $emails = [];
        $educationalEmails = EducationalProgramEmailContactsModel::factory()->count(3)->create([
            'educational_program_uid' => $educational_program->uid,
        ]);

        // foreach($educationalEmails as $educationalEmail){
        //     $emails[]=[
        //         $educationalEmail->email,
        //     ];
        // }    

        // Datos de prueba
        $data = [
            'educational_program_uid' => $educational_program->uid,
            'action' => 'submit',
            'name' => 'Programa Educativo de Prueba',
            'educational_program_type_uid' => $programType->uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'INSTALLMENT_PAYMENT',
            'payment_terms' => json_encode([
                [
                    'uid'         => $educationPayment->uid,
                    'name'        => 'CASH',
                    'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
                    'finish_date' => Carbon::now()->addDays(10)->format('Y-m-d\TH:i'),
                    'cost'        => 100,
                ]
            ]),

            'contact_emails' => json_encode(['fay.levi@hotmail.com', 'derek.jacobson@yahoo.com', 'johathan.schulist@gmail.com']),
            'cost' => 100,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
    }



    /**
     * @test
     * Verifica que un programa educativo existente se actualiza correctamente.
     */
    public function testUpdatesAnExistingEducationalProgram()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
            'certidigital_url'              => 'https://certidigital-k8s.atica.um.es',
            'certidigital_client_id'        => 'certidigi-admin',
            'certidigital_client_secret'    => 'aKli757XUHqVIDC9cu8iwIH4U64qvM7T',
            'certidigital_username'         => 'eadmon.umu@gmail.com',
            'certidigital_password'         => 'wEVZ3rDar10',
            'certidigital_url_token'        => 'https://certidigital-k8s.atica.um.es/realms/certidigi/protocol/openid-connect/token',
            'certidigital_center_id'        => 105,
            'certidigital_organization_oid' => 29,

        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        CategoriesModel::factory()->count(4)->create();

        $categories = CategoriesModel::all();

        $uids =[];

        foreach($categories as $categorie){
            $uids[]=[
                $categorie->uid
            ];
        }

        // Crear un programa educativo existente
        $existingProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'name' => 'Programa Educativo Original',
        ]);    

        // Datos de prueba para la actualización
        $data = [
            'educational_program_uid' => $existingProgram->uid,
            // 'educational_program_type_uid' => $programType->uid,
            'name' => 'Programa Educativo Actualizado',
            'educational_program_type_uid' => $existingProgram->educational_program_type_uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'title'=>'Sin titulo',
            'evaluation_criteria' => 'Nuevos Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 150,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'documents' => json_encode([]),
            'categories'=> json_encode($uids),
            'image_path' => UploadedFile::fake()->image('imagen01.jpg'),
            'featured_slider_image_path' => UploadedFile::fake()->image('slider_image.jpg')
        ];

     

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Programa formativo actualizado correctamente']);

        // Verificar que los cambios se hayan guardado en la base de datos
        $this->assertDatabaseHas('educational_programs', ['uid' => $existingProgram->uid, 'name' => 'Programa Educativo Actualizado']);
    }

    /**
     * @test
     * Verifica que un programa educativo existente tenga error 400 
     */

    public function testUpdatesAnExistingEducationalProgramWithError400()
    {


        // Ejecutar error 400 status->code = INTRODUCTION
        $user = UsersModel::factory()->create();

        $role = UserRolesModel::where('code', 'ADMINISTRATOR')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);

        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
        ]);

        $status = EducationalProgramStatusesModel::where('code', 'DEVELOPMENT')->first();

        $existingProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
            'name' => 'Programa Educativo Original',
            'educational_program_status_uid' => $status,
        ]);

        // Datos de prueba para la actualización
        $data = [
            'educational_program_uid' => $existingProgram->uid,
            // 'educational_program_type_uid' => $programType->uid,
            'name' => 'Programa Educativo Actualizado',
            'educational_program_type_uid' => $existingProgram->educational_program_type_uid,
            'inscription_start_date' => '2024-09-01',
            'inscription_finish_date' => '2024-09-10',  // Debe ser anterior a enrolling_start_date
            'enrolling_start_date' => "2024-09-11",  // Debe ser posterior a inscription_finish_date
            'enrolling_finish_date' => "2024-09-15", // Debe ser posterior a enrolling_start_date
            'realization_start_date' => "2024-09-16", // Debe ser posterior a enrolling_finish_date
            'realization_finish_date' => "2024-09-20", // Debe ser posterior a realization_start_date
            'validate_student_registrations' => 1,
            'evaluation_criteria' => 'Nuevos Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 150,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'documents' => json_encode([]),
        ];

        $randomNumberCategories = rand(1, 5);
        for ($i = 0; $i < $randomNumberCategories; $i++) {
            $data['categories'][] = generate_uuid();
        }

        $data['categories'] = json_encode($data['categories']);


        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);
        // Verificar que la respuesta sea 400 (OK) y contenga el mensaje esperado
        $response->assertStatus(400);
        $response->assertJson(['message' => 'No puedes editar un programa formativo en este estado']);

        
    }

    /**
     * @test
     * Verifica que la validación de un programa educativo falle con datos incorrectos.
     */
    public function testFailsToCreateAnEducationalProgramWithInvalidData()
    {
        // Crear un usuario y asignarle un rol
        $user = UsersModel::factory()->create();
        $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
        $user->roles()->sync([
            $role->uid => ['uid' => generate_uuid()]
        ]);
        Auth::login($user);

        app()->instance('general_options', [
            'necessary_approval_editions' => true,
            'operation_by_calls' => false,
        ]);

        // Datos de prueba con errores (por ejemplo, sin nombre)
        $data = [
            'educational_program_type_uid' => generate_uuid(),
            'inscription_start_date' => '2024-09-01 10:00:00',
            'inscription_finish_date' => '2024-08-30 09:00:00',
        ];

        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);

        // Verificar que la respuesta sea 400 (Bad Request) y contenga el mensaje de error esperado
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Algunos campos son incorrectos']);

        // Verificar que el programa educativo no se haya guardado en la base de datos
        $this->assertDatabaseMissing('educational_programs', ['name' => null]);
    }

    /** @test */
    // public function testReturnsPendingApprovalStatusOrNoStatus()
    // {
    //     // Simulamos los estados del modelo como objetos stdClass
    //     $statuses = collect([
    //         (object)['code' => 'INTRODUCTION'],
    //         (object)['code' => 'PENDING_APPROVAL'],
    //         (object)['code' => 'UNDER_CORRECTION_APPROVAL'],
    //         (object)['code' => 'UNDER_CORRECTION_PUBLICATION'],
    //         (object)['code' => 'PENDING_PUBLICATION'],
    //     ]);

    //     // Mockear el modelo para devolver los estados simulados
    //     $mock = Mockery::mock(EducationalProgramStatusesModel::class);
    //     $mock->shouldReceive('whereIn')
    //         ->with('code', [
    //             'INTRODUCTION',
    //             'PENDING_APPROVAL',
    //             'UNDER_CORRECTION_APPROVAL',
    //             'UNDER_CORRECTION_PUBLICATION',
    //             'PENDING_PUBLICATION'
    //         ])
    //         ->andReturnSelf();
    //     $mock->shouldReceive('get')
    //         ->andReturn($statuses); // Asegúrate de que esto devuelva una colección de stdClass

    //     // Reemplazar el modelo mockeado
    //     $this->app->instance(EducationalProgramStatusesModel::class, $mock);

    //     // Crear una instancia del controlador
    //     $controller = new EducationalProgramsController();

    //     // Usar reflexión para acceder al método privado
    //     $reflection = new \ReflectionClass($controller);
    //     $method = $reflection->getMethod('statusEducationalProgramUserTeacher');
    //     $method->setAccessible(true);

    //     // Probar el caso cuando el estado actual es INTRODUCTION
    //     $result = $method->invokeArgs($controller, ['submit', 'INTRODUCTION']);
    //     $this->assertEquals('PENDING_APPROVAL', $result->code);
    //     // Probar el caso cuando no hay estado actual
    //     $result = $method->invokeArgs($controller, ['submit', null]);
    //     $this->assertEquals('PENDING_APPROVAL', $result->code);

    //     // Probar el caso cuando el estado actual es UNDER_CORRECTION_APPROVAL
    //     $result = $method->invokeArgs($controller, ['submit', 'UNDER_CORRECTION_APPROVAL']);
    //     $this->assertEquals('PENDING_APPROVAL', $result->code);
    //     // Probar el caso cuando el estado actual es UNDER_CORRECTION_PUBLICATION
    //     $result = $method->invokeArgs($controller, ['submit', 'UNDER_CORRECTION_PUBLICATION']);
    //     $this->assertEquals('PENDING_PUBLICATION', $result->code);
    //     // Probar el caso cuando el action es "draft" y el estado actual es INTRODUCTION
    //     $result = $method->invokeArgs($controller, ['draft', 'INTRODUCTION']);
    //     $this->assertEquals('INTRODUCTION', $result->code);

    //     // Probar el caso cuando el action es "draft" y no hay estado actual
    //     $result = $method->invokeArgs($controller, ['draft', null]);
    //     $this->assertEquals('INTRODUCTION', $result->code);

    //     // Probar el caso cuando el action no es "submit" ni "draft"
    //     $result = $method->invokeArgs($controller, ['other_action', 'INTRODUCTION']);
    //     $this->assertNull($result);
    // }

    // public function testSaveChangeStatusInscriptionsEducationalProgram()
    // {
    //     $user = UsersModel::factory()->create();
    //     $role = UserRolesModel::where('code', 'MANAGEMENT')->first();
    //     $user->roles()->sync([
    //         $role->uid => ['uid' => generate_uuid()]
    //     ]);
    //     Auth::login($user);

    //     $educationalProgram = EducationalProgramsModel::factory()->withEducationalProgramType()->create();
    //     $educationalProgramsStudent = EducationalProgramsStudentsModel::factory()->count(3)->create(
    //         [
    //             'educational_program_uid'=> $educationalProgram->uid,
    //             'user_uid'=> $user->uid
    //         ]
    //     );

    //     $data=[
    //         'uids' => [
    //             $educationalProgramsStudent[0]->uid, 
    //             $educationalProgramsStudent[1]->uid, 
    //             $educationalProgramsStudent[2]->uid
    //         ],
    //         'status'=>'ACCEPTED',
    //     ];


    // }

}
