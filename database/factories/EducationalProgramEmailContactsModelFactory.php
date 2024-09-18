<?php

namespace Database\Factories;

use App\Models\EducationalProgramsModel;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalProgramEmailContactsModel>
 */
class EducationalProgramEmailContactsModelFactory extends Factory
{

    public function definition(): array
    {
        return [
            'uid' => Str::uuid(),
            'email' => $this->faker->email(),
        ];
    }
}
