<?php

namespace Database\Factories;

use App\Models\UsersModel;
use App\Models\AutomaticResourceAprovalUsersModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomaticResourceAprovalUsersModel>
 */
class AutomaticResourceAprovalUsersModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        return [
            'uid'=> generateUuid(),
            'user_uid' => UsersModel::factory()->create()->first(),
        ];
    }
}
