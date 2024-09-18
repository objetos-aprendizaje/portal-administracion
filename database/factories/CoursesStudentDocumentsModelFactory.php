<?php

namespace Database\Factories;

use App\Models\UsersModel;
use App\Models\CourseDocumentsModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesStudentDocumentsModel>
 */
class CoursesStudentDocumentsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid'=> generate_uuid(),            
            'user_uid' => UsersModel::factory()->create()->first(),
            'course_document_uid' => CourseDocumentsModel::factory()->withCourseUid()->create()->first(),
            'document_path' => 'storage/',
        ];
    }
}
