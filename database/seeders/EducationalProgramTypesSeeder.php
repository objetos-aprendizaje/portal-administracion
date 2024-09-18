<?php

namespace Database\Seeders;

use App\Models\EducationalProgramTypesModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EducationalProgramTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 0; $i < 3; $i++) {
            EducationalProgramTypesModel::factory()->create();
        }
    }
}
