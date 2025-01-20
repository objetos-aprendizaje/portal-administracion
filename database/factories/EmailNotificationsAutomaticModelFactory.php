<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailNotificationsAutomaticModel>
 */
class EmailNotificationsAutomaticModelFactory extends Factory
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
            'sent' => false,
            'user_uid' => generateUuid(),
            'subject' => 'Test Subject',
            'parameters' => json_encode(['key' => 'value']), // Ejemplo de parámetros
            'template' => 'notification_template',
        ];
    }
}
