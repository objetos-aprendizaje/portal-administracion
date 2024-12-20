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
            'uid' => generate_uuid(),
            'notification_type_uid' => Str::uuid(),
            'subject' => $this->faker->unique()->sentence(3),
            'body' => 'Cuerpo de Ejemplo',
            'sent' => 0
        ];
    }
}
