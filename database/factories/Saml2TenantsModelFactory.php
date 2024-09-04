<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Saml2TenantsModel>
 */
class Saml2TenantsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => generate_uuid(),
            'idp_entity_id' => generate_uuid(),
            'idp_login_url' => generate_uuid(),
            'idp_logout_url' => generate_uuid(),
            'idp_x509_cert' => generate_uuid(),
            'metadata' =>  json_encode([
                'key1' => $this->faker->word,
                'key2' => $this->faker->numberBetween(1, 100),
                'key3' => $this->faker->boolean,
            ]),
            'name_id_format' => 'name_id_format'
        ];
    }
}
