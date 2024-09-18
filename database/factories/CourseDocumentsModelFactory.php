<?php

namespace Database\Factories;

use App\Models\CoursesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseDocumentsModel>
 */
class CourseDocumentsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => generate_uuid(),
            'document_name'=> $this->faker->word(3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withCourseUid(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_uid' => CoursesModel::factory()
            ->withCourseStatus()
            ->withCourseType()
            ->create()->first(),
        ]);
    }
}
