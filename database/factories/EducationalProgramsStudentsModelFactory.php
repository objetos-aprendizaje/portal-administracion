<?php

namespace Database\Factories;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalProgramsStudentsModel>
 */
class EducationalProgramsStudentsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => generateUuid(),
            'educational_program_uid' => generateUuid(),
            'user_uid' => generateUuid(),
            'acceptance_status' => 'PENDING',
            'status' => 'INSCRIBED',
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
