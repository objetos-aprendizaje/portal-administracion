<?php

namespace Tests\Unit;


use Tests\TestCase;

use App\Models\UsersModel;

use App\Models\CompetencesModel;
use Illuminate\Support\Facades\Schema;


class LearningObjectProgramsEducationalTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        // Asegúrate de que la tabla 'qvkei_settings' existe
        $this->assertTrue(Schema::hasTable('users'), 'La tabla users no existe.');
    } // Configuración inicial si es necesario


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
            'uid' => generateUuid(),
            'name' => 'Subcompetence 1',
            'parent_competence_uid' => $competence->uid // Establecer la relación padre
        ])->first();

        CompetencesModel::factory()->create([
            'uid' => generateUuid(),
            'name' => 'Subcompetence 2',
            'parent_competence_uid' => $subcompetence1->uid // Establecer la relación padre
        ])->first();


        CompetencesModel::factory()->create(['uid' => generateUuid(), 'name' => 'Competence 2'])->latest()->first();


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
