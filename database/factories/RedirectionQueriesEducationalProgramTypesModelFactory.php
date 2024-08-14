<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RedirectionQueriesEducationalProgramTypesModel>
 */
class RedirectionQueriesEducationalProgramTypesModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'educational_program_type_uid' => $this->faker->uuid(),
            'type' => $this->faker->randomElement(['web', 'email']),
            'contact' => $this->generateContact(),
        ];
    }

    private function generateContact(): string
    {
        // Generar una URL y truncarla a un máximo de 200 caracteres
        $url = $this->faker->url();
        return substr($url, 0, 20);
    }
}
