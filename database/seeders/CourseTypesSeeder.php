<?php

namespace Database\Seeders;

use App\Models\CourseTypesModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 0; $i < 3; $i++) {
            CourseTypesModel::factory()->create();
        }
    }
}
