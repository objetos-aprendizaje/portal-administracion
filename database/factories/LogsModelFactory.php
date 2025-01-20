<?php

namespace Database\Factories;

use App\Models\UsersModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogsModel>
 */
class LogsModelFactory extends Factory
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
            'info' => 'Pruebas unitarias',
            'entity' => 'Entity',
        ];
    }

    private function withUser(): static
    {
        return $this->state(fn () => [
            'user_uid' => UsersModel::factory()->create()->first(),
        ]);
    }
}
