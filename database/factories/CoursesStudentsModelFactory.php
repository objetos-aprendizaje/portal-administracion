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
            'uid'               => generate_uuid(),
            'course_uid'        => CoursesModel::factory()->create()->first(), 
            'user_uid'          => UsersModel  ::factory()->create()->first(), 
            'calification_type' => 'NUMERIC',
            'status'            => 'INSCRIBED',
            'acceptance_status' => 'PENDING',
        ];//
        
    }
}
