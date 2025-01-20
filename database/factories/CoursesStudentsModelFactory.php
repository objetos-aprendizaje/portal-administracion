<?php

namespace Database\Factories;

use App\Models\UsersModel;
use App\Models\CoursesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesStudentsModel>
 */
class CoursesStudentsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid'               => generateUuid(),
            'status'            => 'INSCRIBED',
            'acceptance_status' => 'PENDING',
        ];
    }

    public function withCourse(): Factory
    {
        return $this->state(function () {
            return [
                'course_uid'        => CoursesModel::factory()
                ->withCourseStatus()
                ->withCourseType()
                ->create()
                ->first(),
            ];
        });
    }

    public function withUser(): Factory
    {
        return $this->state(function () {
            return [
                'user_uid'          => UsersModel::factory()->create()->first(),
            ];
        });
    }
}
