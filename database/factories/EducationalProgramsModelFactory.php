<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalProgramsModel>
 */
class EducationalProgramsModelFactory extends Factory
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
            'educational_program_type_uid' => EducationalProgramTypesModel::factory()->create()->first(),
            'validate_student_registrations' => 1,
            'payment_mode' =>'SINGLE_PAYMENT',
            'featured_slider' => 1,
            'featured_main_carrousel' => 1,
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
       
    }
}
