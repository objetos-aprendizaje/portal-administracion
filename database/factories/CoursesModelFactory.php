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

        // Contar el número de cursos existentes
        $courseCount = CoursesModel::count();

        // Generar el identificador en el formato 'CUR-0001'
        $identifier = 'CUR-' . str_pad($courseCount + 1, 4, '0', STR_PAD_LEFT);

        return [
            'uid' => generate_uuid(),
            'course_lms_uid' => generate_uuid(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'ects_workload' => $this->faker->numberBetween(1, 10),
            'belongs_to_educational_program' => $this->faker->boolean(),
            'identifier' => $identifier,
            'presentation_video_url' => $this->faker->url(),
            'calification_type' => $this->faker->randomElement(['NUMERICAL', 'TEXTUAL']),
            'lms_url' => $this->faker->url(),
            'cost' => $this->faker->randomFloat(2, 0, 1000),

        ];
    }

    public function withCourseStatus(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_status_uid' => CourseStatusesModel::factory()->create()->first(),
        ]);
    }

    public function withCourseType(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_type_uid' => CourseTypesModel::factory()->create()->first(),
        ]);
    }
}
