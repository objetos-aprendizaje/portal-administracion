<?php

namespace Database\Factories;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as FakerFactory;
use App\Faker\NifProvider;
use App\Models\UserRolesModel;
use App\Models\UsersModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsersModel>
 */
class UsersModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = FakerFactory::create();
        $faker->addProvider(new NifProvider($faker));

        return [
            'uid' => generate_uuid(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => fake()->unique()->safeEmail(),
            'password' => password_hash('1234', PASSWORD_BCRYPT),
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'nif' => $faker->nif(),
            'verified' => 1
        ];
    }
}
