<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationsChangesStatusesCoursesModel>
 */
class NotificationsChangesStatusesCoursesModelFactory extends Factory
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
            'user_uid' => generate_uuid(),
            'course_uid' => Str::uuid(),
            'course_status_uid' => Str::uuid(),
            'date' => Carbon::now()->format('Y-m-d\TH:i'),
            'is_read' => 0,
        ];
    }
}
