<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserRoleRelationshipsModel>
 */
class UserRoleRelationshipsModelFactory extends Factory
{

    public function definition(): array
    {
        return [
            'uid' => generate_uuid(),
        ];
    }
}
