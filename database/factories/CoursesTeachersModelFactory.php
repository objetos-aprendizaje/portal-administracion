<?php

namespace Database\Factories;

use Illuminate\Support\Carbon;
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
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
