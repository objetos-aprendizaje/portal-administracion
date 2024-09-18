<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursesPaymentTermsUsersModel>
 */
class CoursesPaymentTermsUsersModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uid' => generate_uuid(),
            'order_number' => $this->faker->numberBetween(100000000000, 999999999999),
        ];
    }
}
