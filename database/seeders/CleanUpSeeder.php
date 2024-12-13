<?php

namespace Database\Seeders;

use App\Models\CallsModel;
use App\Models\CategoriesModel;
use App\Models\CentersModel;
use App\Models\CompetenceFrameworksModel;
use App\Models\CompetencesModel;
use App\Models\CourseDocumentsModel;
use App\Models\CourseGlobalCalificationsModel;
use App\Models\CoursesAccesesModel;
use App\Models\CoursesModel;
use App\Models\CoursesStudentsDocumentsModel;
use App\Models\CoursesStudentsModel;
use App\Models\CourseTypesModel;
use App\Models\EducationalProgramEmailContactsModel;
use App\Models\EducationalProgramsAssessmentsModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramsPaymentTermsModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalsProgramsCategoriesModel;
use App\Models\LearningResultsModel;
use App\Models\LicenseTypesModel;
use App\Models\LmsSystemsModel;
use App\Models\UserGeneralNotificationsModel;
use App\Models\UsersAccessesModel;
use App\Models\UsersModel;
use Illuminate\Database\Seeder;

class CleanUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CoursesAccesesModel::query()->delete();
        CourseGlobalCalificationsModel::query()->delete();
        EducationalProgramsAssessmentsModel::query()->delete();
        CoursesModel::query()->delete();
        EducationalProgramsModel::query()->delete();
        CallsModel::query()->delete();
        EducationalProgramTypesModel::query()->delete();
        CategoriesModel::query()->delete();
        CentersModel::query()->delete();
        CompetenceFrameworksModel::query()->delete();
        CompetencesModel::query()->delete();
        LearningResultsModel::query()->delete();
        CourseTypesModel::query()->delete();
        CoursesStudentsModel::query()->delete();
        EducationalProgramsStudentsModel::query()->delete();
        LmsSystemsModel::query()->delete();
        UserGeneralNotificationsModel::query()->delete();
        UsersModel::query()->delete();
        EducationalProgramEmailContactsModel::query()->delete();
        EducationalsProgramsCategoriesModel::query()->delete();
        EducationalProgramsPaymentTermsModel::query()->delete();
        EducationalProgramsStudentsModel::query()->delete();
        EducationalResourcesModel::query()->delete();
        LicenseTypesModel::query()->delete();
        CourseDocumentsModel::query()->delete();
        CoursesStudentsDocumentsModel::query()->delete();
        UsersAccessesModel::query()->delete();
    }
}
