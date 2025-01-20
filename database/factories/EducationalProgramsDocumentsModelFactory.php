<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalProgramsDocumentsModel>
 */
class EducationalProgramsDocumentsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid'=> generateUuid(),
            'document_name'=> $this->faker->sentence(2),
        ];
    }
}
