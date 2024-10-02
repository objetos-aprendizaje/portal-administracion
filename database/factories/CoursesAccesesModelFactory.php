<?php

namespace Database\Factories;

use App\Models\CoursesAccesesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesAccesesModel>
 */
class CoursesAccesesModelFactory extends Factory
{
    protected $model = CoursesAccesesModel::class;

    public function definition(): array
    {
        return [
            'uid' => generate_uuid(),
        ];
    }
}
