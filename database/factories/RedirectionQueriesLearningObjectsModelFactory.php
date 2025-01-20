<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RedirectionQueriesLearningObjectsModel>
 */
class RedirectionQueriesLearningObjectsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => generateUuid(),
            // 'educational_program_type_uid' =>"",
            'type' =>  $this->faker->randomElement(['web', 'email']),
            'contact' =>$this->faker->name(),
            'learning_object_type' => $this->faker->randomElement(['EDUCATIONAL_PROGRAM', 'COURSE']),
            // 'course_type_uid' =>''
        ];
    }
}
