<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class UsersModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => Str::uuid(),
            'first_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => '$2y$10$vu2dWbPxPtGQeEVyjNiQtu0H.4zWzCPgJkuqXSPgHMWxhOAbhyTFC', // 1234

        ];
    }
}
