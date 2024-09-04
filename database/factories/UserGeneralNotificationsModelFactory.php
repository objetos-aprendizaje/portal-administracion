<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserGeneralNotificationsModel>
 */
class UserGeneralNotificationsModelFactory extends Factory
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
            'user_uid' => Str::ulid(),
            'general_notification_uid' => Str::ulid(),
            'view_date' => now(),
        ];
    }
}
