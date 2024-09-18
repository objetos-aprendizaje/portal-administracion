<?php

namespace Database\Seeders;

use App\Models\EducationalResourceTypesModel;
use Illuminate\Database\Seeder;

class EducationalResourceTypesSeeder extends Seeder
{

    public function run(): void
    {

        for($i = 0; $i < 3; $i++) {
            EducationalResourceTypesModel::factory()->create();
        }
    }
}
