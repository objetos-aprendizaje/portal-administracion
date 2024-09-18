<?php

namespace Database\Seeders;

use App\Models\CategoriesModel;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourceStatusesModel;
use App\Models\EducationalResourceTypesModel;
use App\Models\LearningResultsModel;
use App\Models\LicenseTypesModel;
use App\Models\UsersModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Faker\Factory as Faker;

class EducationalResourcesSeeder extends Seeder
{
    protected $categories;
    protected $learningResults;
    protected $educationalResourceStatuses;
    protected $educationalResourceTypes;
    protected $demoImages;
    protected $demoPdfs;
    protected $demoVideos;
    protected $demoAudios;
    protected $teachers;
    protected $licenseTypes;
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();

        $this->categories = CategoriesModel::all()->pluck('uid');
        $this->learningResults = LearningResultsModel::all()->pluck('uid');
        $this->educationalResourceStatuses = EducationalResourceStatusesModel::all()->keyBy('code');
        $this->educationalResourceTypes = EducationalResourceTypesModel::all()->keyBy('code');
        $this->licenseTypes = LicenseTypesModel::all();

        $this->teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->get();

        $this->demoImages = collect(File::files(base_path('public/images/test-images')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();

        $this->demoVideos = collect(File::files(base_path('public/test-videos')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();

        $this->demoPdfs = collect(File::files(base_path('public/test-documents')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();

        $this->demoAudios = collect(File::files(base_path('public/test-audios')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {

            $resourceWay =  $this->faker->randomElement(['URL', 'FILE', 'IMAGE', 'PDF', 'VIDEO', 'AUDIO']);

            $data = [
                'image_path' => $this->demoImages[array_rand($this->demoImages)],
                'educational_resource_type_uid' => $this->educationalResourceTypes->random()->uid,
                'creator_user_uid' => $this->teachers->random()->uid,
                'resource_way' => $resourceWay,
                'status_uid' => $this->educationalResourceStatuses['PUBLISHED']->uid,
                'license_type_uid' => $this->licenseTypes->random()->uid,
            ];

            if (in_array($resourceWay, ["FILE", "IMAGE"])) {
                $data['resource_path'] = $this->demoImages[array_rand($this->demoImages)];
            } else if ($resourceWay == "URL") {
                $data['resource_url'] = $this->faker->url();
            } else if ($resourceWay == "PDF") {
                $data['resource_path'] = $this->demoPdfs[array_rand($this->demoPdfs)];
            } else if ($resourceWay == "VIDEO") {
                $data['resource_path'] = $this->demoVideos[array_rand($this->demoVideos)];
            } else if ($resourceWay == "AUDIO") {
                $data['resource_path'] = $this->demoAudios[array_rand($this->demoAudios)];
            }

            EducationalResourcesModel::factory()->create($data);
        }
    }

}
