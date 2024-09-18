<?php

namespace Database\Factories;

use App\Models\CoursesTeachersModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesModel>
 */
class CoursesTeachersModelFactory extends Factory
{

    protected $model = CoursesTeachersModel::class;

    public function definition(): array
    {

        return [
            'uid' => generate_uuid(),
        ];
    }
}
