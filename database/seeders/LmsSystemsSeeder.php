<?php

namespace Database\Seeders;

use App\Models\LmsSystemsModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LmsSystemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 0; $i < 3; $i++) {
            LmsSystemsModel::factory()->create();
        }
    }
}
