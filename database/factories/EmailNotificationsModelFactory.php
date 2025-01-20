<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailNotificationsModel>
 */
class EmailNotificationsModelFactory extends Factory
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
            'notification_type_uid' => Str::uuid(),
            'subject' => $this->faker->unique()->sentence(3),
            'body' => 'Cuerpo de Ejemplo',
            'status' => 'SENT',
            // 'status' => $this->faker->randomElement(['SENT', 'FAILED']),
        ];
    }
}
