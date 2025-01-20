<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BackendFileDownloadTokensModel>
 */
class BackendFileDownloadTokensModelFactory extends Factory
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
            'token'=> generateUuid(),
            'file' => 'prueba.text'
        ];
    }
}
