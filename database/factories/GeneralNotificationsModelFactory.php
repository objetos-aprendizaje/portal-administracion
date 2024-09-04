<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\NotificationsTypesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeneralNotificationsModel>
 */
class GeneralNotificationsModelFactory extends Factory
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
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->unique()->paragraph(2),
            'notification_type_uid' => NotificationsTypesModel::factory()->create()->first(),
            'start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
