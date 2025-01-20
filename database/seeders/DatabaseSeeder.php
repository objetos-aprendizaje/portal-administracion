<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // CleanUpSeeder::class,
            UsersSeeder::class,
            CallsSeeder::class,
            CourseTypesSeeder::class,
            EducationalProgramTypesSeeder::class,
            CentersSeeder::class,
            LmsSystemsSeeder::class,
            CategoriesSeeder::class,
            CompetencesLearningResultsSeeder::class,
            CoursesSeeder::class,
            EducationalProgramsSeeder::class,
            EducationalResourceTypesSeeder::class,
            LicenseTypesSeeder::class,
            EducationalResourcesSeeder::class,
            UserAccessesSeeder::class
        ]);
    }
}
