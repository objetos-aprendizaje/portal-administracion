<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\CourseTypesModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesModel>
 */
class CoursesModelFactory extends Factory
{

    protected $model = CoursesModel::class;

    public function definition(): array
    {
        return [
            'uid' => Str::uuid(),
            'title' => $this->faker->sentence(),
            'course_status_uid' => CourseStatusesModel::factory()->create()->first(), // Esto generará un estado de curso automáticamente
            'course_type_uid' => CourseTypesModel::factory()->create()->first(), // Asegúrate de que este también esté definido
            'ects_workload' => $this->faker->numberBetween(1, 10),
            'belongs_to_educational_program' => $this->faker->boolean(),
            'identifier' => 'identifier',
        ];
    }
}
