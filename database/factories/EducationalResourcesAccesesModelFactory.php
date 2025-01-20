<?php

namespace Database\Factories;

use App\Models\EducationalResourcesAccesesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalResourcesAccesesModel>
 */
class EducationalResourcesAccesesModelFactory extends Factory
{
    protected $model = EducationalResourcesAccesesModel::class;

    public function definition(): array
    {
        return [
            'uid' => generateUuid(),
        ];
    }
}
