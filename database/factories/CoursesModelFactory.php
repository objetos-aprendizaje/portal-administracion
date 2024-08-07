<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\CourseTypesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesModel>
 */
class CoursesModelFactory extends Factory
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
            'title' => $this->faker->sentence(),
            'course_status_uid' => CourseStatusesModel::factory(), // Esto generará un estado de curso automáticamente
            'course_type_uid' => CourseTypesModel::factory(), // Asegúrate de que este también esté definido
            'ects_workload' => $this->faker->numberBetween(1, 10),
            'belongs_to_educational_program' => $this->faker->boolean(),
            'identifier' => 'identifier',
        ];
    }
}
