<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeneralNotificationsAutomaticModel>
 */
class GeneralNotificationsAutomaticModelFactory extends Factory
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
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->unique()->paragraph(2),
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'automatic_notification_type_uid' => AutomaticNotificationTypesModel::factory()->create()->first(),
        ];
    }
}
