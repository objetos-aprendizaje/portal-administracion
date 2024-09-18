<?php

namespace Database\Seeders;

use App\Models\CentersModel;
use Illuminate\Database\Seeder;

class CentersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 3; $i++) {
            CentersModel::factory()->create();
        }
    }
}
