<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallsModel>
 */
class CallsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => Str::uuid(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d\TH:i'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d\TH:i'),
        ];
    }
}
