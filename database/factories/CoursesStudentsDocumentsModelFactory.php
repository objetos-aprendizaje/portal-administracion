<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesStudentsDocumentsModel>
 */
class CoursesStudentsDocumentsModelFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'uid' => generate_uuid(),
            'document_path' => $this->faker->url(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
