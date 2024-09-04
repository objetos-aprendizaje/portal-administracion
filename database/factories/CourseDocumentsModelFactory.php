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
            'course_uid' => CoursesModel::factory()->create()->first(),
            'document_name'=> $this->faker->word
        ];
    }
}
