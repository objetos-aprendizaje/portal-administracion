<?php

namespace Database\Seeders;

use App\Models\LicenseTypesModel;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class LicenseTypesSeeder extends Seeder
{

    public function run(): void
    {
        for($i = 0; $i < 3; $i++) {
            LicenseTypesModel::factory()->create();
        }
    }

}
