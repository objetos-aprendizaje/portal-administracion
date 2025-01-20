<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CertidigitalAssesmentsModel>
 */
class CertidigitalAssesmentsModelFactory extends Factory
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
            'id'=> $this->faker->numberBetween(1,100),
            'title'=> $this->faker->words(3, true),
            'description' => $this->faker->paragraph(4),

        ];
    }
}
