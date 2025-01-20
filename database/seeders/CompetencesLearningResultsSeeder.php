<?php

namespace Database\Seeders;

use App\Models\CompetenceFrameworksLevelsModel;
use App\Models\CompetenceFrameworksModel;
use App\Models\CompetencesModel;
use App\Models\LearningResultsModel;
use Illuminate\Database\Seeder;

class CompetencesLearningResultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Marco de competencias
        $competenceFrameworkUid = $this->createCompetenceFramework();

        // Competencias
        $this->createCompetences($competenceFrameworkUid, 10);
    }

    /**
     * Create the competence framework.
     *
     * @return string
     */
    private function createCompetenceFramework(): string
    {
        $competenceFrameworkUid = generateUuid();
        CompetenceFrameworksModel::factory()->create([
            'uid' => $competenceFrameworkUid,
            'has_levels' => true,
        ]);

        $this->createCompetenceFrameworkLevels($competenceFrameworkUid);

        return $competenceFrameworkUid;
    }

    private function createCompetenceFrameworkLevels(string $competenceFrameworkUid): void
    {

        $numberOfLevels = rand(3, 5);
        for ($i = 0; $i < $numberOfLevels; $i++) {
            CompetenceFrameworksLevelsModel::factory()->create([
                'competence_framework_uid' => $competenceFrameworkUid,
            ]);
        }
    }

    /**
     * Create competences and their subcompetences.
     *
     * @param string $parentUid
     * @param int $count
     */
    private function createCompetences(string $competenceFrameworkUid, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $competenceUid = generateUuid();
            CompetencesModel::factory()->create([
                'uid' => $competenceUid,
                'competence_framework_uid' => $competenceFrameworkUid,
            ]);

            // Generamos un número de subcompetencias aleatorias para cada competencia
            $subCompetences = rand(1, 5);
            $this->createSubCompetences($competenceUid, $subCompetences, $competenceFrameworkUid);
        }
    }

    /**
     * Create subcompetences and their learning outcomes.
     *
     * @param string $parentUid
     * @param int $count
     */
    private function createSubCompetences(string $parentUid, int $count, $competenceFrameworkUid): void
    {
        for ($j = 0; $j < $count; $j++) {
            $subcompetenceUid = generateUuid();
            CompetencesModel::factory()->create([
                'uid' => $subcompetenceUid,
                'parent_competence_uid' => $parentUid,
                'competence_framework_uid' => $competenceFrameworkUid,
            ]);

            // Resultados de aprendizaje
            $learningOutcomes = rand(1, 5);
            $this->createLearningOutcomes($subcompetenceUid, $learningOutcomes);
        }
    }

    /**
     * Create learning outcomes for a subcompetence.
     *
     * @param string $competenceUid
     * @param int $count
     */
    private function createLearningOutcomes(string $competenceUid, int $count): void
    {
        for ($x = 0; $x < $count; $x++) {
            LearningResultsModel::factory()->create([
                'competence_uid' => $competenceUid,
            ]);
        }
    }
}
