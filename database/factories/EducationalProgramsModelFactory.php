<?php

namespace Database\Factories;

use App\Models\EducationalProgramsModel;
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
        // Contar el número de cursos existentes
        $courseCount = EducationalProgramsModel::count();

        // Generar el identificador en el formato 'CUR-0001'
        $identifier = 'PF-' . str_pad($courseCount + 1, 4, '0', STR_PAD_LEFT);

        return [
            'uid' => Str::uuid(),
            'name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'inscription_start_date' => Carbon::now()->format('Y-m-d\TH:i'),
            'inscription_finish_date' => Carbon::now()->addDays(30)->format('Y-m-d\TH:i'),
            'enrolling_start_date' => Carbon::now()->addDays(31)->format('Y-m-d\TH:i'),
            'enrolling_finish_date' => Carbon::now()->addDays(60)->format('Y-m-d\TH:i'),
            'realization_start_date' => Carbon::now()->addDays(61)->format('Y-m-d\TH:i'),
            'realization_finish_date' => Carbon::now()->addDays(90)->format('Y-m-d\TH:i'),
            'identifier' => $identifier,
            'validate_student_registrations' => 1,
            'payment_mode' => 'SINGLE_PAYMENT',
            'featured_slider' => 1,
            'featured_main_carrousel' => 1,
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }

    public function withEducationalProgramType(): static
    {
        return $this->state(fn(array $attributes) => [
            'educational_program_type_uid' => EducationalProgramTypesModel::factory()->create()->first(),
        ]);
    }
}
