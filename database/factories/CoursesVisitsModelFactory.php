<?php

namespace Database\Factories;

use App\Models\CoursesVisitsModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesAccesesModel>
 */
class CoursesVisitsModelFactory extends Factory
{
    protected $model = CoursesVisitsModel::class;

    public function definition(): array
    {
        return [
            'uid' => generateUuid(),
        ];
    }
}
