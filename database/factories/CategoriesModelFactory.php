<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategoriesModel>
 */
class CategoriesModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $demoImages = collect(File::files(base_path('public/test-images')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();

        return [
            'uid' => generate_uuid(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->paragraph(2),
            'color' => '#fff',
            'image_path' => $demoImages[array_rand($demoImages)],
            'created_at' => Carbon::now()->format('Y-m-d\TH:i'),
            'updated_at' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
