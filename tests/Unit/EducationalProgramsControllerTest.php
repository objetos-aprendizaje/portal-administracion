<?php

namespace Tests\Unit;

use Log;
use Mockery;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\CallsModel;
use App\Models\UsersModel;
use Illuminate\Support\Str;
use App\Models\CoursesModel;
use App\Models\UserRolesModel;
use App\Models\CategoriesModel;
use App\Models\LmsSystemsModel;
use App\Models\CourseTypesModel;
use App\Models\TooltipTextsModel;
use Illuminate\Http\UploadedFile;
use App\Models\CourseStatusesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\CertidigitalService;
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
use App\Models\EducationalProgramEmailContactsModel;
use App\Models\EducationalProgramsPaymentTermsModel;
use App\Models\EducationalProgramsStudentsDocumentsModel;

class EducationalProgramsControllerTest extends TestCase
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
        $roles = UserRolesModel::firstOrCreate(['code' => 'MANAGEMENT'], ['uid' => generate_uuid()]); // Crea roles de prueba
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
    }

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

    /** @test Calcula la mediana de estudiantes inscritos en categorías. */
    public function testCalculateMedianEnrollingsCategoriesEducationalProgram()
    {
        // Simulando autenticación del usuario
        $user = UsersModel::factory()->create();
        // $user->roles()->attach(UserRolesModel::factory()->create(['code' => 'ADMIN']));
        $roles = UserRolesModel::where('code', 'ADMINISTRATOR')->first();

        $user->roles()->attach($roles->uid, ['uid' => generate_uuid()]);

        $this->actingAs($user);

        $users = UsersModel::factory()->count(3)->create();


        // Crear una categoría
        $category = CategoriesModel::factory()->create();

        // Crear programas educativos con estudiantes
        $programs = EducationalProgramsModel::factory()->count(3)
            ->withEducationalProgramType()
            ->create();

        // Asignar la categoría a cada programa y crear estudiantes con inscripción aceptada
        foreach ($programs as $program) {
            $program->categories()->attach($category->uid, [
                'uid' => generate_uuid(),
            ]);
            // Crear estudiantes inscritos con estado ENROLLED y aceptación ACCEPTED
            EducationalProgramsStudentsModel::factory()->create([
                'educational_program_uid' => $program->uid,
                'status' => 'ENROLLED',
                'acceptance_status' => 'ACCEPTED',
                'user_uid' => $user->uid,
            ]);
        }

        // Realizar la solicitud POST a la ruta con el UID de la categoría
        $response = $this->postJson('/learning_objects/educational_programs/calculate_median_enrollings_categories', [
            'categories_uids' => [$category->uid]
        ]);

        // Calcular manualmente la mediana esperada
        $expectedMedian = calculateMedian([3, 3, 3]);

        // Verificar que la respuesta sea exitosa y que la mediana sea correcta
        $response->assertStatus(200);
        // $response->assertJson(['median' => $expectedMedian]);
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
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program/' . generate_uuid());

        // Verificar la respuesta
        $response->assertStatus(406)
            ->assertJson([
                'message' => 'El programa formativo no existe',
            ]);
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
            'necessary_approval_editions'   => true,
            'operation_by_calls'            => false,
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),

        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        $lms = LmsSystemsModel::factory()->create();

        $courses = CoursesModel::factory()->count(3)->withCourseStatus()->withCourseType()->create(
            [
                'lms_system_uid' => $lms->uid,
            ]
        );
        // $course = CoursesModel::where('uid', $course->uid)->first();
        // dd($course); // Verifica que este curso exista

        // Datos de prueba
        $data = [
            'action' => 'submit',
            'courses' => [$courses[0]->uid, $courses[1]->uid, $courses[2]->uid],
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

        // die();
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
            // 'courses' => [$course->uid],
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
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),
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
            'title' => 'Title',
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


        //////////////
        // Para cubrir linea de status = UNDER_CORRECTION_APPROVAL 177
        $status = EducationalProgramStatusesModel::where('code', 'UNDER_CORRECTION_APPROVAL')->first();

        $educational_program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(
            [
                'educational_program_status_uid' => $status->uid,
            ]
        );

        // Datos de prueba
        $data = [
            'action' => 'submit',
            'educational_program_uid' => $educational_program->uid,
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
            'title' => 'Title',
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];
        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);
        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);

        //////////////
        // Para cubrir linea de status = UNDER_CORRECTION_PUBLICATION 179
        $status = EducationalProgramStatusesModel::where('code', 'UNDER_CORRECTION_PUBLICATION')->first();

        $educational_program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(
            [
                'educational_program_status_uid' => $status->uid,
            ]
        );

        // Datos de prueba
        $data = [
            'action' => 'submit',
            'educational_program_uid' => $educational_program->uid,
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
            'title' => 'Title',
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];
        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);
        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);

        //////////////
        // Para cubrir linea de $action === "draft" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION") 182
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
            'title' => 'Title',
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'categories' => json_encode([generate_uuid(), generate_uuid()]),
            'documents' => json_encode([]),
            //Asegúrate de pasar un JSON válido o un array vacío
        ];
        // Realizar la solicitud POST
        $response = $this->postJson('/learning_objects/educational_programs/save_educational_program', $data);
        // Verificar que la respuesta sea 200 (OK) y contenga el mensaje esperado
        $response->assertStatus(200);

        //////////////
        // Para cubrir linea de $action === "" distinto de submit y draft 184
        // Datos de prueba
        $data = [
            'action' => 'save',
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
            'title' => 'Title',
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
            'certidigital_url'              => env('CERTIDIGITAL_URL'),
            'certidigital_client_id'        => env('CERTIDIGITAL_CLIENT_ID'),
            'certidigital_client_secret'    => env('CERTIDIGITAL_CLIENT_SECRET'),
            'certidigital_username'         => env('CERTIDIGITAL_USERNAME'),
            'certidigital_password'         => env('CERTIDIGITAL_PASSWORD'),
            'certidigital_url_token'        => env('CERTIDIGITAL_URL_TOKEN'),
            'certidigital_center_id'        => env('CERTIDIGITAL_CENTER_ID'),
            'certidigital_organization_oid' => env('CERTIDIGITAL_ORGANIZATION_OID'),

        ]);

        // Crear un Educational Program Type de prueba
        $programType = EducationalProgramTypesModel::factory()->create();

        CategoriesModel::factory()->count(4)->create();

        $categories = CategoriesModel::all();

        $uids = [];

        foreach ($categories as $categorie) {
            $uids[] = [
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
            'title' => 'Sin titulo',
            'evaluation_criteria' => 'Nuevos Criterios de Evaluación',
            'courses' => [generate_uuid()],
            'payment_mode' => 'SINGLE_PAYMENT',
            'cost' => 150,
            'tags' => json_encode(['Etiqueta1', 'Etiqueta2']),
            'documents' => json_encode([]),
            'categories' => json_encode($uids),
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
        ])->latest()->first();;

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
        $response->assertJsonMissing(['title' => 'Curso de Biología']);
    }

    /** @test Cambiar estatus de Programa educativo */
    public function testChangeStatusesOfEducationalPrograms()
    {
        // Crear un usuario administrador
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

        // Sincronizar roles y actuar como administrador
        $admin->roles()->sync($roles_to_sync);
        $this->actingAs($admin);

        if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

            // Crear estado aprobado
            $statusApproved = EducationalProgramStatusesModel::factory()->create(['code' => 'APPROVED']);

            // Crear programa educativo con todos los campos necesarios
            $uidProgram = generate_uuid();
            $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
                'uid' => $uidProgram,
                'name' => 'Nombre del Programa', // Asegúrate de incluir este campo
                'status_reason' =>  "Prueba unitaria",
                'educational_program_status_uid' => $statusApproved->uid, // Asegúrate de incluir este campo también
                'creator_user_uid' => $admin->uid // Asegúrate de incluir el creador
            ]);

            // Mockear el CertidigitalService
            $certidigitalServiceMock = Mockery::mock(CertidigitalService::class);
            // Configurar expectativas según lo que necesites
            // Por ejemplo, si hay un método que se llama en el controlador, puedes mockearlo así:
            // $certidigitalServiceMock->shouldReceive('someMethod')->andReturn($expectedValue);

            // Reemplazar el servicio en el contenedor de servicios
            app()->instance(CertidigitalService::class, $certidigitalServiceMock);

            // Hacer la solicitud POST a la ruta correspondiente
            $response = $this->post('/learning_objects/educational_programs/change_statuses_educational_programs', [
                'changesEducationalProgramsStatuses' => [
                    ['uid' => $uidProgram, 'status' => 'APPROVED']
                ]
            ]);

            // Verificar la respuesta
            $this->assertEquals(200, $response->status());

            // Asegúrate de que 'message' sea parte de la respuesta.
            $this->assertEquals('Se han actualizado los estados de los programas formativos correctamente', $response->getData()->message);

            // Verificar que se haya actualizado el estado del programa educativo
            // Asegúrate de que haya una relación correcta para acceder al estado.
            $this->assertEquals('Name Status', $program->fresh()->status['name']);  // Cambia aquí si es necesario

            Notification::fake();
            Bus::fake();

            // Verificar que el trabajo de notificación fue despachado
            // Bus::assertDispatched(SendChangeStatusEducationalProgramNotification::class);
        }
    }

    /** @test Cambiar estatus de Programa educativo sin autorización*/
    public function testChangeStatusesEducationalProgramsUnauthorized()
    {
        // Configuramos un usuario sin roles
        $user = UsersModel::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);


        $response = $this->post('/learning_objects/educational_programs/change_statuses_educational_programs', [
            'changesEducationalProgramsStatuses' => []
        ]);

        $response->assertStatus(403);

        $response->assertJson([
            'message' => 'No tienes permisos para realizar esta acción',
        ]);
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


            $response = $this->post('/learning_objects/educational_programs/change_statuses_educational_programs', [
                'changesEducationalProgramsStatuses' => null
            ]);

            $response->assertStatus(406);

            $response->assertJson([
                'message' => 'No se han enviado los datos correctamente',
            ]);
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
            return [$student->uid => ['uid' => (string) Str::uuid(), 'acceptance_status' => 'ACCEPTED']];
        });
        $program->students()->attach($attachments);

        // Simular la petición
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/' . $program->uid);

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
                'acceptance_status' => 'ACCEPTED',
            ]);
        }

        // Simular la petición con un filtro de búsqueda
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/' . $program->uid . '?search=Julio');

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
        $uidProgram = generate_uuid();
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => $uidProgram]);

        // Crear estudiantes y asignarlos al programa
        $studentA = UsersModel::factory()->create(['first_name' => 'Alice', 'last_name' => 'Zephyr']);
        $studentB = UsersModel::factory()->create(['first_name' => 'Bob', 'last_name' => 'Young']);
        $program->students()->attach($studentA->uid, ['uid' => (string) Str::uuid(), 'acceptance_status' => 'ACCEPTED']);
        $program->students()->attach($studentB->uid, ['uid' => (string) Str::uuid(), 'acceptance_status' => 'ACCEPTED']);

        // Simular la petición con ordenamiento
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/' . $uidProgram . '?sort[0][field]=first_name&sort[0][dir]=asc&size=10');

        // Verificar la respuesta
        $response->assertStatus(200);
        $this->assertEquals('Alice', $response->json('data.0.first_name'));
        $this->assertEquals('Bob', $response->json('data.1.first_name'));
    }

    /** @test Obtiene Programas educativos */
    public function testGetEducationalProgramStudentsWithPagination()
    {
        // Crear un programa educativo
        $uidProgram = generate_uuid();
        $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create(['uid' => $uidProgram]);

        // Crear estudiantes y asignarlos al programa
        $students = UsersModel::factory()->count(10)->create();
        $attachments = $students->mapWithKeys(function ($student) {
            return [$student->uid => ['uid' => (string) Str::uuid(), 'acceptance_status' => 'ACCEPTED']];
        });
        $program->students()->attach($attachments);

        // Simular la petición con paginación
        $response = $this->getJson('/learning_objects/educational_programs/get_educational_program_students/' . $uidProgram . '?size=5');

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

    /** @test Cambia el estado de las inscripciones y genera notificaciones automáticas. */

    public function testChangeStatusInscriptionsEducationalProgram()
    {
        // Crear usuario docente
        $user_teacher = UsersModel::factory()->create();

        // Crear programa educativo
        $educationalProgram = EducationalProgramsModel::factory()
            ->withEducationalProgramType()
            ->create();

        // Crear inscripciones de estudiantes en el programa educativo
        $students = UsersModel::factory()->count(2)->create();
        $studentUids = [];
        foreach ($students as $student) {
            $studentRegistration = EducationalProgramsStudentsModel::factory()->create([
                'educational_program_uid' => $educationalProgram->uid,
                'user_uid' => $student->uid,
                'acceptance_status' => 'PENDING'
            ]);
            $studentUids[] = $studentRegistration->uid;
        }

        // Realizar la solicitud POST con los UIDs de los estudiantes y el nuevo estado
        $this->actingAs($user_teacher);
        $response = $this->postJson('/learning_objects/educational_program/change_status_inscriptions_educational_program', [
            'uids' => $studentUids,
            'status' => 'ACCEPTED'
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        $response->assertJson(['message' => 'Estados de inscripciones cambiados correctamente']);

        // Verificar que el estado de aceptación de los estudiantes haya cambiado en la base de datos
        foreach ($studentUids as $uid) {
            $this->assertDatabaseHas('educational_programs_students', [
                'uid' => $uid,
                'acceptance_status' => 'ACCEPTED'
            ]);
        }

        // Verificar que se han creado notificaciones automáticas generales para los estudiantes
        foreach ($students as $student) {
            $this->assertDatabaseHas('general_notifications_automatic_users', [
                'user_uid' => $student->uid
            ]);
            $this->assertDatabaseHas('email_notifications_automatic', [
                'user_uid' => $student->uid,
                'parameters' => json_encode(['educational_program_title' => $educationalProgram->title, 'status' => 'ACCEPTED'])
            ]);
        }


        // Realizar la solicitud POST con los UIDs de los estudiantes y el nuevo estado
        $this->actingAs($user_teacher);
        $response = $this->postJson('/learning_objects/educational_program/change_status_inscriptions_educational_program', [
            'uids' => $studentUids,
            'status' => 'REJECTED'
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        $response->assertJson(['message' => 'Estados de inscripciones cambiados correctamente']);
    }

    // /** @test Puede inscribir estudiantes desde csv */
    public function testCanEnrollStudentsFromCsv()
    {
        // Crea un usuario autenticado para la prueba
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Crea dos usuarios y obtén sus datos
        $user1 = UsersModel::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nif' => '28632229N',
            'email' => 'john@example.com',
        ]);

        $user2 = UsersModel::factory()->create([
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
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Realiza la solicitud POST a la ruta
        $response = $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que el mensaje de respuesta sea el esperado
        $response->assertJson(['message' => 'Alumnos añadidos al curso. Los ya registrados no se han añadido.']);

        // Verifica que los estudiantes se hayan inscrito correctamente
        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user1->uid,
            'acceptance_status' => 'ACCEPTED',
        ]);

        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => $user2->uid,
            'acceptance_status' => 'ACCEPTED',
        ]);
    }

    /** @test Valida que se arroje una excepción si el NIF o el correo no son válidos */
    public function testInvalidNifThrowsException()
    {
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Simula un archivo CSV con NIF y correo inválidos
        Storage::fake('local');
        $csvContent = "first_name,last_name,nif,email\n" .
            "John,Doe,invalid_nif,johnexample.com";
        $csvFile = UploadedFile::fake()->createWithContent('students_invalid.csv', $csvContent);

        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Captura la excepción lanzada y verifica el mensaje de error
        try {
            $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);
        } catch (OperationFailedException $e) {
            $this->assertEquals("El NIF/NIE de la línea 1 no es válido", $e->getMessage());
        }
    }

    /** @test Valida que se arroje una excepción si el correo no es válido */
    public function testInvalidEmailThrowsException()
    {
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Simula un archivo CSV con un correo inválido
        Storage::fake('local');
        $csvContent = "first_name,last_name,nif,email\n" .
            "John,Doe,28632229N,invalid_email";
        $csvFile = UploadedFile::fake()->createWithContent('students_invalid_email.csv', $csvContent);

        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Captura la excepción lanzada y verifica el mensaje de error
        try {
            $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);
        } catch (OperationFailedException $e) {
            $this->assertEquals("El correo de la línea 1 no es válido", $e->getMessage());
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
            'uid' => generate_uuid(),
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
            'acceptance_status' => 'ACCEPTED',
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



    //Todo este prueba pertenece al controlador ManagementCoursesController
    /** @test Puede registrar e inscribir un nuevo usuario desde csv */
    public function testCanSignUpAndEnrollNewUserFromCsv()
    {
        $this->actingAs(UsersModel::factory()->create());

        // Crea un curso
        $course = CoursesModel::factory()->withCourseStatus()->withCourseType()->create();
        $courseUid = $course->uid;

        // Simula un archivo CSV con datos de un usuario nuevo
        Storage::fake('local');
        $csvContent = "first_name,last_name,nif,email\n" .
            "New,User,28632229N,newuser@example.com";
        $csvFile = UploadedFile::fake()->createWithContent('new_student.csv', $csvContent);

        $requestData = [
            'course_uid' => $courseUid,
            'attachment' => $csvFile,
        ];

        // Realiza la solicitud POST
        $response = $this->postJson('/learning_objects/courses/enroll_students_csv', $requestData);

        // Verifica que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verifica que el usuario nuevo se haya registrado y se haya inscrito en el curso
        $this->assertDatabaseHas('users', [
            'nif' => '28632229N',
            'email' => 'newuser@example.com',
        ]);

        $this->assertDatabaseHas('courses_students', [
            'course_uid' => $courseUid,
            'user_uid' => UsersModel::where('email', 'newuser@example.com')->first()->uid,
            'acceptance_status' => 'ACCEPTED',
        ]);
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
}
