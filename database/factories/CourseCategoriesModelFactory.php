<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseCategoriesModel>
 */
class CourseCategoriesModelFactory extends Factory
{

    public function definition(): array
    {
        return [
            'uid' => generateUuid(),
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
