<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeaderPagesModel>
 */
class HeaderPagesModelFactory extends Factory
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
            'order' => 1,
            'name' => 'nombre pagina header',
            'content' => 'Contenido',
            'slug' => 'nueva-pagina-header',
        ];
    }
}
