<?php

namespace Database\Seeders;

use App\Models\CallsModel;
use App\Models\CategoriesModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\CourseTypesModel;
use App\Models\EducationalProgramEmailContactsModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramsPaymentTermsModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\EducationalsProgramsCategoriesModel;
use App\Models\LmsSystemsModel;
use App\Models\UsersModel;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\File;

class EducationalProgramsSeeder extends Seeder
{

    protected $faker;
    protected $calls;
    protected $demoImages;
    protected $educationalProgramStatuses;
    protected $courseStatuses;
    protected $teachers;
    protected $educationalProgramTypes;
    protected $courseTypes;
    protected $centers;
    protected $lmsSystems;
    protected $categories;
    protected $students;


    public function __construct()
    {
        $this->faker = Faker::create();
        $this->calls = CallsModel::all();
        $this->demoImages = collect(File::files(base_path('public/test-images')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();
        $this->educationalProgramStatuses = EducationalProgramStatusesModel::all()->keyBy('code');
        $this->teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->get();
        $this->educationalProgramTypes = EducationalProgramTypesModel::all();
        $this->courseTypes = CourseTypesModel::all();
        $this->centers = CentersModel::all();
        $this->lmsSystems = LmsSystemsModel::all();
        $this->courseStatuses = CourseStatusesModel::all()->keyBy('code');
        $this->categories = CategoriesModel::all();
        $this->students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })->get();
    }


    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Todo: Implement the logic to seed the educational programs table
        $this->addInscriptionEducationalPrograms();
        $this->addEnrollingEducationalPrograms();
        $this->addRealizationEducationalPrograms();
        $this->addFinishedEducationalPrograms();
    }

    private function addInscriptionEducationalPrograms()
    {
        $educationalProgramsDataset = $this->getEducatinalProgramsDataset(120, 10);

        for ($i = 0; $i < 10; $i++) {
            $customData = [
                'name' => $educationalProgramsDataset[$i]['title'],
                'description' => $educationalProgramsDataset[$i]['description'],
                'payment_mode' => 'SINGLE_PAYMENT',

                'inscription_start_date' => adjustDateRandomly('-1 year'),
                'inscription_finish_date' => adjustDateRandomly('+1 month'),

                'enrolling_start_date' => adjustDateRandomly('+2 month'),
                'enrolling_finish_date' => adjustDateRandomly('+1 year'),

                'realization_start_date' => adjustDateRandomly('+13 month'),
                'realization_finish_date' => adjustDateRandomly('+2 year'),
            ];

            $this->createEducationalProgram($customData, 'INSCRIPTION');
        }
    }

    private function addEnrollingEducationalPrograms()
    {
        $educationalProgramsDataset = $this->getEducatinalProgramsDataset(130, 10);

        for ($i = 0; $i < 10; $i++) {
            $customData = [
                'name' => $educationalProgramsDataset[$i]['title'],
                'description' => $educationalProgramsDataset[$i]['description'],
                'payment_mode' => 'SINGLE_PAYMENT',

                'inscription_start_date' => adjustDateRandomly('-2 year'),
                'inscription_finish_date' => adjustDateRandomly('-1 year'),

                'enrolling_start_date' => adjustDateRandomly('-11 month'),
                'enrolling_finish_date' => adjustDateRandomly('+1 year'),

                'realization_start_date' => adjustDateRandomly('+13 month'),
                'realization_finish_date' => adjustDateRandomly('+2 year'),
            ];

            $this->createEducationalProgram($customData, 'ENROLLING');
        }
    }

    private function addRealizationEducationalPrograms()
    {
        $educationalProgramsDataset = $this->getEducatinalProgramsDataset(140, 10);

        for ($i = 0; $i < 10; $i++) {
            $customData = [
                'name' => $educationalProgramsDataset[$i]['title'],
                'description' => $educationalProgramsDataset[$i]['description'],
                'payment_mode' => $this->faker->randomElement(['INSTALLMENT_PAYMENT', 'SINGLE_PAYMENT']),

                'inscription_start_date' => adjustDateRandomly('-2 year'),
                'inscription_finish_date' => adjustDateRandomly('-1 year'),

                'enrolling_start_date' => adjustDateRandomly('-11 month'),
                'enrolling_finish_date' => adjustDateRandomly('-10 month'),

                'realization_start_date' => adjustDateRandomly('-9 month'),
                'realization_finish_date' => adjustDateRandomly('+1 year'),
            ];

            $this->createEducationalProgram($customData, 'DEVELOPMENT');
        }
    }

    private function addFinishedEducationalPrograms()
    {
        $educationalProgramsDataset = $this->getEducatinalProgramsDataset(150, 10);
        for ($i = 0; $i < 10; $i++) {
            $customData = [
                'name' => $educationalProgramsDataset[$i]['title'],
                'description' => $educationalProgramsDataset[$i]['description'],
                'payment_mode' => 'SINGLE_PAYMENT',

                'inscription_start_date' => adjustDateRandomly('-3 year'),
                'inscription_finish_date' => adjustDateRandomly('-2 year'),

                'enrolling_start_date' => adjustDateRandomly('-23 month'),
                'enrolling_finish_date' => adjustDateRandomly('-1 year'),

                'realization_start_date' => adjustDateRandomly('-11 month'),
                'realization_finish_date' => adjustDateRandomly('-1 month'),
            ];

            $this->createEducationalProgram($customData, 'FINISHED');
        }
    }

    private function createEducationalProgram($customData, $status)
    {

        $educationalProgramUid = generate_uuid();

        $data = [
            'uid' => $educationalProgramUid,
            'call_uid' => $this->calls->random(),
            'image_path' => $this->demoImages[array_rand($this->demoImages)],
            'min_required_students' => 1,
            'educational_program_type_uid' => $this->educationalProgramTypes->random()->uid,
            'validate_student_registrations' => true,
            'cost' => $this->faker->randomFloat(2, 100, 1000),
            'featured_slider' => rand(0, 1),
            'featured_main_carrousel' => rand(0, 1),
            'educational_program_status_uid' => $this->educationalProgramStatuses[$status]->uid,
            'creator_user_uid' => $this->teachers->random()->uid,
            'featured_slider_approved' => true,
            'featured_main_carrousel_approved' => true,
        ];

        if ($data['featured_slider']) {
            $data['featured_slider_title'] = $this->faker->sentence(3);
            $data['featured_slider_description'] = $this->faker->sentence(10);
            $data['featured_slider_image_path'] = $this->demoImages[array_rand($this->demoImages)];
            $data['featured_slider_color_font'] = $this->faker->hexColor;
        }

        $data = array_merge($data, $customData);

        EducationalProgramsModel::factory()->create($data);

        $this->addCategories($educationalProgramUid);
        $this->addEmailsContacts($educationalProgramUid);
        $this->addStudents($educationalProgramUid, $status);

        if ($customData['payment_mode'] == 'INSTALLMENT_PAYMENT') $this->addPaymentTerms($educationalProgramUid, $customData['realization_start_date'], $customData['realization_finish_date']);


        $numberCourses = $this->faker->numberBetween(2, 5);
        for ($i = 0; $i < $numberCourses; $i++) {
            $this->createCourse($educationalProgramUid, $customData['realization_start_date'], $customData['realization_finish_date']);
        }
    }

    private function addPaymentTerms($educationalProgramUid, $startDate, $finishDate)
    {

        $paymentTerms = rand(1, 5);
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($finishDate);
        $interval = ($endTimestamp - $startTimestamp) / $paymentTerms;

        for ($i = 0; $i < $paymentTerms; $i++) {
            $intervalStart = $startTimestamp + ($i * $interval);
            $intervalEnd = $intervalStart + $interval;

            EducationalProgramsPaymentTermsModel::factory()->create([
                'educational_program_uid' => $educationalProgramUid,
                'start_date' => date('Y-m-d H:i:s', $intervalStart),
                'finish_date' => date('Y-m-d H:i:s', $intervalEnd),
            ]);
        }
    }

    private function createCourse($educationalProgramUid, $realizationStartDate, $realizationFinishDate)
    {
        $data = [
            'uid' => generate_uuid(),
            'belongs_to_educational_program' => true,
            'educational_program_uid' => $educationalProgramUid,
            'image_path' => $this->demoImages[array_rand($this->demoImages)],
            'educational_program_type_uid' => $this->educationalProgramTypes->random()->uid,
            'course_status_uid' => $this->courseStatuses['ADDED_EDUCATIONAL_PROGRAM']->uid,
            'course_type_uid' => $this->courseTypes->random()->uid,
            'cost' => null,
            'payment_mode' => 'SINGLE_PAYMENT',
            'featured_big_carrousel_title' => null,
            'featured_big_carrousel_description' => null,
            'realization_start_date' => $realizationStartDate,
            'realization_finish_date' => $realizationFinishDate,
            'creator_user_uid' => $this->teachers->random()->uid,
            'center_uid' => $this->centers->random()->uid,
            'lms_system_uid' => $this->lmsSystems->random()->uid,
            'featured_big_carrousel_approved' => false,
            'featured_small_carrousel_approved' => false,
        ];

        CoursesModel::factory()->create($data);
    }

    private function addEmailsContacts($educationalProgramUid)
    {
        EducationalProgramEmailContactsModel::factory()->create([
            'educational_program_uid' => $educationalProgramUid,
        ]);
    }

    private function addCategories($courseUid)
    {
        EducationalsProgramsCategoriesModel::factory()->create([
            'educational_program_uid' => $courseUid,
            'category_uid' => $this->categories->random(),
        ]);
    }


    private function addStudents($educationalProgramUid, $status)
    {
        if ($status == "INSCRIPTION") {
            $student = $this->students->random();
            EducationalProgramsStudentsModel::factory()->create([
                'educational_program_uid' => $educationalProgramUid,
                'user_uid' => $student,
                'status' => 'INSCRIBED',
                'acceptance_status' => $this->faker->randomElement(['PENDING', 'ACCEPTED', 'REJECTED']),
            ]);
        } else {
            foreach ($this->students as $student) {
                EducationalProgramsStudentsModel::factory()->create([
                    'educational_program_uid' => $educationalProgramUid,
                    'user_uid' => $student,
                    'status' => $this->faker->randomElement(['ENROLLED', 'INSCRIBED']),
                    'acceptance_status' => 'ACCEPTED',
                ]);
            }
        }
    }

    private function getEducatinalProgramsDataset($start, $end)
    {
        $csv = readCsv('database/seeders/dataset_learning_objects.csv');

        // Filtrar los cursos que se encuentran entre las posiciones start y end
        $courses = array_slice($csv, $start, $end);
        return $courses;
    }
}
