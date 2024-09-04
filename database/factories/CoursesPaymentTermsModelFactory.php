<?php

namespace Database\Factories;

use Illuminate\Support\Carbon;
use App\Models\CoursesModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesPaymentTermsModel>
 */
class CoursesPaymentTermsModelFactory extends Factory
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
            'course_uid'=> CoursesModel::factory()->create()->first(),
            'name'=>'Card',            
            'start_date' => Carbon::now()->addDays(1),            
            'finish_date' =>Carbon::now()->addDays(10),
            'cost' =>50,
        ];
    }
}
