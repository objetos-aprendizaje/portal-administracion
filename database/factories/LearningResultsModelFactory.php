<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\CompetencesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningResultsModel>
 */
class LearningResultsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid'=> Str::uuid(),
            'name' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph(2),
            'competence_uid' => CompetencesModel::factory()->create()->first(),

        ];
    }
}
