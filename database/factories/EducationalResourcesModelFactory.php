<?php

namespace Database\Factories;

use App\Models\EducationalResourcesModel;
use App\Models\UsersModel;
use App\Models\EducationalResourceTypesModel;
use App\Models\EducationalResourceStatusesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalResourcesModel>
 */
class EducationalResourcesModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Contar el número de cursos existentes
        $educationalResourcesCount = EducationalResourcesModel::count();

        // Generar el identificador en el formato 'CUR-0001'
        $identifier = 'REC-' . str_pad($educationalResourcesCount + 1, 4, '0', STR_PAD_LEFT);

        return [
            'uid'                           => generate_uuid(),
            'identifier'                    => $identifier,
            'title'                         => $this->faker->sentence(3),
            'description'                   => $this->faker->sentence,
            'resource_way'                  => 'URL',
            'created_at'                    => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at'                    => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function withStatus(): static
    {
        return $this->state(fn(array $attributes) => [
            'status_uid'                    => EducationalResourceStatusesModel::factory()->create()->first(),
        ]);
    }

    public function withEducationalResourceType(): static
    {
        return $this->state(fn(array $attributes) => [
            'educational_resource_type_uid' => EducationalResourceTypesModel::factory()->create()->first(),
        ]);
    }

    public function withCreatorUser(): static
    {
        return $this->state(fn(array $attributes) => [
            'creator_user_uid' => UsersModel::factory()->create()->first(),
        ]);
    }
}
