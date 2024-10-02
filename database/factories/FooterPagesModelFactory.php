<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FooterPagesModel>
 */
class FooterPagesModelFactory extends Factory
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
            'name' => $this->faker->unique()->sentence(3),
            'content' => 'Contenido original del pie de página',
            'slug' => 'footer-page-original',
            'acceptance_required' => 1,
        ];
    }
}
