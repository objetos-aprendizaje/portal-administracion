<?php

namespace Database\Factories;

use App\Models\CoursesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlocksModel>
 */
class BlocksModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid'        => generate_uuid(),
            'name'       => $this->faker->word,
            'description' => $this->faker->sentence,
            'order'      => $this->faker->randomNumber,
            'type'       => $this->faker->randomElement(['THEORETIC', 'PRACTICAL', 'EVALUATION']),
        ];
    }

    public function withCourse(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'course_uid' => CoursesModel::factory()->create()->first(),
            ];
        });
    }
}
