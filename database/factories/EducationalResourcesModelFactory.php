<?php

namespace Database\Factories;

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
        return [
            'uid'                           => generate_uuid(),
            'title'                         => $this->faker->word,  
            'status_uid'                    => EducationalResourceStatusesModel::factory()->create()->first(),
            'educational_resource_type_uid' => EducationalResourceTypesModel   ::factory()->create()->first(),
            'creator_user_uid'              => UsersModel                      ::factory()->create()->first(),
            'resource_way'                  => 'URL',
        ];
    }
}
