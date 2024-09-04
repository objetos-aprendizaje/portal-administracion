<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailsSuggestionsModel>
 */
class EmailsSuggestionsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
           'uid'=>generate_uuid(),
           'email' => fake()->unique()->safeEmail(),
           'name' => $this->faker->name,
           'message' => $this->faker->unique()->paragraph(2),
        ];
    }
}
