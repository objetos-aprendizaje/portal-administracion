<?php

namespace Tests\Unit;

use Mockery;
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
use App\Models\EducationalProgramsStudentsDocumentsModel;
use App\Jobs\SendChangeStatusEducationalProgramNotification;
use App\Http\Controllers\LearningObjects\EducationalProgramsController;

class LearningObjectProgramsEducationalTest extends TestCase
{
    

    /** @test redirección Programa educativos */
    public function testRedirectionQueryProgramsEducational()
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


    


    

    /** @test Cambiar estatus de Programa educativo*/
    // public function testChangeStatusesOfEducationalPrograms()
    // {
    //     $admin = UsersModel::factory()->create();
    //     $roles_bd = UserRolesModel::get()->pluck('uid');
    //     $roles_to_sync = [];
    //     foreach ($roles_bd as $rol_uid) {
    //         $roles_to_sync[] = [
    //             'uid' => generate_uuid(),
    //             'user_uid' => $admin->uid,
    //             'user_role_uid' => $rol_uid
    //         ];
    //     }

    //     $admin->roles()->sync($roles_to_sync);
    //     $this->actingAs($admin);

    //     if ($admin->hasAnyRole(['ADMINISTRATOR'])) {

    //         $statusApproved = EducationalProgramStatusesModel::factory()->create(['code' => 'APPROVED']);
    //         $uidProgram = generate_uuid();
    //         $program = EducationalProgramsModel::factory()->withEducationalProgramType()->create([
    //             'uid' => $uidProgram,
    //             'status_reason' => $statusApproved->uid,
    //         ]);

    //         // Mock del request
    //         $request = Request::create('/learning_objects/educational_programs/change_statuses_educational_programs', 'POST', [
    //             'changesEducationalProgramsStatuses' => [
    //                 ['uid' => $uidProgram, 'status' => 'APPROVED']
    //             ]
    //         ]);
            

    //         // Llamada al controlador
    //         $controller = new EducationalProgramsController();

    //         // Desactivamos notificaciones y trabajos para pruebas
    //         Notification::fake();
    //         Bus::fake();

    //         $response = $controller->changeStatusesEducationalPrograms($request);

    //         // Verificamos la respuesta
    //         $this->assertEquals(200, $response->status());
    //         $this->assertEquals('Se han actualizado los estados de los programas formativos correctamente', $response->getData()->message);

    //         // Verificamos que se haya actualizado el estado
    //         $this->assertEquals('APPROVED', $program->fresh()->status->code);

    //         // Verificamos que el trabajo de notificación fue despachado
    //         Bus::assertDispatched(SendChangeStatusEducationalProgramNotification::class);
    //     }
    // }

   

  

    

    

   

   

    

    

    

    

    

   

    // :::::::::::::::::::::::::::::: Esta parte pertenece al Modulo LearningObjectProgramsEducationalTest :::::::::::::::

    


   

    


    

    /**
     * @test Obtener todas las competencias de Tipo de programa Educacional
     */
    public function testGetAllCompetencesEducationalProgramType()
    {
        $user = UsersModel::factory()->create();
        $this->actingAs($user);

        // Crear competencias de prueba
        $competence = CompetencesModel::factory()->create()->latest()->first();
        $this->assertDatabaseHas('competences', ['uid' => $competence->uid]);

        // Crear subcompetencias asociadas a competence1
        $subcompetence1 = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Subcompetence 1',
            'parent_competence_uid' => $competence->uid // Establecer la relación padre
        ])->first();

        $subcompetence2 = CompetencesModel::factory()->create([
            'uid' => generate_uuid(),
            'name' => 'Subcompetence 2',
            'parent_competence_uid' => $subcompetence1->uid // Establecer la relación padre
        ])->first();


        $competence2 = CompetencesModel::factory()->create(['uid' => generate_uuid(), 'name' => 'Competence 2'])->latest()->first();


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
