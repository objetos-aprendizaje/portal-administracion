<?php

namespace Database\Seeders;

use App\Models\CategoriesModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoImages = collect(File::files(base_path('public/test-images')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();

        for ($i = 0; $i <= 5; $i++) {
            CategoriesModel::factory()->create([
                'image_path' => $demoImages[array_rand($demoImages)]
            ]);
        }
    }
}
