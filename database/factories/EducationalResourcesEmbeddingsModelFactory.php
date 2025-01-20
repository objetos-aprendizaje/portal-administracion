<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationalResourcesEmbeddingsModel>
 */
class EducationalResourcesEmbeddingsModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $embeddingVector = array_fill(0, 1536, 0.1);
        return [
            'embeddings'=> $embeddingVector,
        ];
    }
}
