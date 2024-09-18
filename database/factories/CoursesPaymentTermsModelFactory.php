<?php

namespace Database\Factories;

use Illuminate\Support\Carbon;
use App\Models\CoursesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesPaymentTermsModel>
 */
class CoursesPaymentTermsModelFactory extends Factory
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
            'name' => $this->faker->words(2, true),
            'start_date' => Carbon::now(),
            'finish_date' => Carbon::now()->addDays(10),
            'cost' => $this->faker->randomFloat(2, 1, 100),
        ];
    }

    public function withCourse(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'course_uid' => CoursesModel::factory()->create()->first(),
            ];
        });
    }
}
