<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResetPasswordTokensModel>
 */
class ResetPasswordTokensModelFactory extends Factory
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
            'email' => 'example@example.com',
            'uid_user' =>Str::uuid(),
            'token' => Str::uuid(),
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
