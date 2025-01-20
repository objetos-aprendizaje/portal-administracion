<?php

namespace Database\Seeders;

use App\Models\BlocksModel;
use App\Models\CallsModel;
use App\Models\CategoriesModel;
use App\Models\CentersModel;
use App\Models\CourseCategoriesModel;
use App\Models\CourseDocumentsModel;
use App\Models\CoursesEmailsContactsModel;
use App\Models\CoursesModel;
use App\Models\CoursesPaymentTermsModel;
use App\Models\CoursesStudentsDocumentsModel;
use App\Models\CoursesStudentsModel;
use App\Models\CourseStatusesModel;
use App\Models\CoursesTeachersModel;
use App\Models\CourseTypesModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\ElementsModel;
use App\Models\LearningResultsBlocksModel;
use App\Models\LearningResultsModel;
use App\Models\LmsSystemsModel;
use App\Models\SubblocksModel;
use App\Models\SubelementsModel;
use App\Models\UsersModel;
use App\Models\CoursesAccesesModel;
use App\Models\CoursesVisitsModel;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CoursesSeeder extends Seeder
{
    protected $faker;
    protected $courseStatuses;
    protected $categories;
    protected $teachers;
    protected $centers;
    protected $courseTypes;
    protected $lmsSystems;
    protected $educationalProgramTypes;
    protected $calls;
    protected $demoImages;
    protected $students;
    protected $learningResults;
    protected $demoDocuments;

    public function __construct()
    {
        $this->faker = Faker::create();
        $this->courseStatuses = CourseStatusesModel::all()->keyBy('code');

        $this->categories = CategoriesModel::all()->pluck('uid');
        $this->teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->get();
        $this->students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })->get();
        $this->centers = CentersModel::all()->pluck('uid');
        $this->courseTypes = CourseTypesModel::all()->pluck('uid');
        $this->lmsSystems = LmsSystemsModel::all()->pluck('uid');
        $this->educationalProgramTypes = EducationalProgramTypesModel::all()->pluck('uid');
        $this->calls = CallsModel::all()->pluck('uid');
        $this->demoImages = collect(File::files(base_path('public/test-images')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();
        $this->demoDocuments = collect(File::files(base_path('public/test-documents')))
            ->map(function ($file) {
                return str_replace(base_path('public/'), '', $file->getPathname());
            })->toArray();
        $this->learningResults = LearningResultsModel::all()->pluck('uid');

    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->addInscriptionCourses();
        $this->addEnrollingCourses();
        $this->addRealizationCourses();
        $this->addFinishedCourses();
    }

    private function addInscriptionCourses()
    {
        $customData = [
            'inscription_start_date' => adjustDateRandomly('-1 year'),
            'inscription_finish_date' => adjustDateRandomly('+1 month'),

            'enrolling_start_date' => adjustDateRandomly('+2 month'),
            'enrolling_finish_date' => adjustDateRandomly('+1 year'),

            'realization_start_date' => adjustDateRandomly('+13 month'),
            'realization_finish_date' => adjustDateRandomly('+2 year'),
        ];

        $courses = $this->getCoursesDataset(0, 30);

        $this->createCourses($customData, 'INSCRIPTION', $courses);
    }

    private function addEnrollingCourses()
    {
        $customData = [
            'inscription_start_date' => adjustDateRandomly('-2 year'),
            'inscription_finish_date' => adjustDateRandomly('-1 year'),

            'enrolling_start_date' => adjustDateRandomly('-11 month'),
            'enrolling_finish_date' => adjustDateRandomly('+1 year'),

            'realization_start_date' => adjustDateRandomly('+13 month'),
            'realization_finish_date' => adjustDateRandomly('+2 year'),
        ];

        $courses = $this->getCoursesDataset(30, 30);

        $this->createCourses($customData, 'ENROLLING', $courses);
    }

    private function addRealizationCourses()
    {
        $customData = [
            'inscription_start_date' => adjustDateRandomly('-2 year'),
            'inscription_finish_date' => adjustDateRandomly('-1 year'),

            'enrolling_start_date' => adjustDateRandomly('-11 month'),
            'enrolling_finish_date' => adjustDateRandomly('-10 month'),

            'realization_start_date' => adjustDateRandomly('-9 month'),
            'realization_finish_date' => adjustDateRandomly('+1 year'),
        ];

        $courses = $this->getCoursesDataset(60, 30);

        $this->createCourses($customData, 'DEVELOPMENT', $courses);
    }

    private function addFinishedCourses()
    {
        $customData = [
            'inscription_start_date' => adjustDateRandomly('-3 year'),
            'inscription_finish_date' => adjustDateRandomly('-2 year'),

            'enrolling_start_date' => adjustDateRandomly('-23 month'),
            'enrolling_finish_date' => adjustDateRandomly('-1 year'),

            'realization_start_date' => adjustDateRandomly('-11 month'),
            'realization_finish_date' => adjustDateRandomly('-1 month'),
        ];

        $courses = $this->getCoursesDataset(90, 30);

        $this->createCourses($customData, 'FINISHED', $courses);
    }

    private function createCourses($customData, $status, $courses)
    {
        foreach ($courses as $course) {
            $baseData = $this->buildCourseBaseData($status);
            $data = array_merge($baseData, $customData);

            $data['title'] = $course['title'];
            $data['description'] = $course['description'];
            CoursesModel::factory()->create($data);

            $this->addCategories($data['uid']);
            $this->addEmailsContacts($data['uid']);
            $this->addTeachers($data['uid']);
            $this->addBlocks($data['uid']);

            $studentsUids = $this->addStudents($data['uid'], $status);

            if($status == "DEVELOPMENT"){

                $this->AddCourseAccesses($data['uid'],$studentsUids);
                $this->AddCourseVisits($data['uid'],$studentsUids);

            }

            // Añadir los documentos necesarios para la validación del curso
            if ($data['validate_student_registrations']) {
                $documentsAddedUids = $this->addDocuments($data['uid']);
                $this->addStudentsDocumentsCourse($documentsAddedUids, $studentsUids);
            }

            // Si es un pago a plazos, se añaden plazos aleatorios comprendidos entre la fecha de inicio y fin de realización
            if ($data['payment_mode'] == 'INSTALLMENT_PAYMENT') {
                $this->addPaymentTerms($data['uid'], $customData['realization_start_date'], $customData['realization_finish_date']);
            }

            //


        }
    }

    private function buildCourseBaseData($status)
    {
        $randomDemoImage = $this->demoImages[array_rand($this->demoImages)];

        $data = [
            'uid' => generateUuid(),
            'course_lms_uid' => generateUuid(),
            'course_status_uid' => $this->courseStatuses[$status]->uid,
            'educational_program_type_uid' => $this->educationalProgramTypes->random(),
            'call_uid' => $this->calls->random(),
            'course_type_uid' => $this->courseTypes->random(),
            'validate_student_registrations' => 1,
            'min_required_students' => 1,
            'image_path' => $randomDemoImage,
            'evaluation_criteria' => $this->faker->paragraph,
            'creator_user_uid' => $this->teachers->random()->uid,
            'center_uid' => $this->centers->random(),
            'lms_system_uid' => $this->lmsSystems->random(),
            'validate_student_registrations' => rand(0, 1),
            'featured_big_carrousel' => rand(0, 1),
            'featured_small_carrousel' => rand(0, 1),
            'belongs_to_educational_program' => 0,
        ];

        if ($data['featured_big_carrousel']) {
            $data['featured_big_carrousel_image_path'] = $randomDemoImage;
            $data['featured_slider_color_font'] = $this->faker->hexColor;
            $data['featured_big_carrousel_title'] = $this->faker->sentence();
            $data['featured_big_carrousel_description'] = $this->faker->paragraph();
            $data['featured_big_carrousel_approved'] = 1;
        }

        if ($data['featured_small_carrousel']) {
            $data['featured_small_carrousel_approved'] = rand(0, 1);
        }

        $data['payment_mode'] = $this->faker->randomElement(['INSTALLMENT_PAYMENT', 'SINGLE_PAYMENT']);

        if ($data['payment_mode'] == 'SINGLE_PAYMENT') {
            $data['cost'] = $this->faker->randomElement([null, $this->faker->randomFloat(2, 100, 1000)]);
        } else {
            $data['cost'] = null;
        }

        $data['validate_student_registrations'] = rand(0, 1);
        $data['evaluation_criteria'] = $data['validate_student_registrations'] ? $this->faker->paragraph : null;

        return $data;
    }

    private function getCoursesDataset($start, $end)
    {
        $csv = readCsv('database/seeders/dataset_learning_objects.csv');

        // Filtrar los cursos que se encuentran entre las posiciones start y end
        return array_slice($csv, $start, $end);
        
    }

    private function addCategories($courseUid)
    {
        CourseCategoriesModel::factory()->create([
            'course_uid' => $courseUid,
            'category_uid' => $this->categories->random(),
        ]);
    }

    private function addEmailsContacts($courseUid)
    {
        CoursesEmailsContactsModel::factory()->create([
            'course_uid' => $courseUid,
            'email' => $this->faker->email,
        ]);
    }

    private function addTeachers($courseUid)
    {
        // Seleccionar el primer user_uid aleatorio
        $firstUserUid = $this->teachers->random();

        // Seleccionar el segundo user_uid aleatorio y asegurarse de que no sea igual al primero
        do {
            $secondUserUid = $this->teachers->random();
        } while ($secondUserUid === $firstUserUid);

        CoursesTeachersModel::factory()->create([
            'course_uid' => $courseUid,
            'user_uid' => $firstUserUid,
            'type' => 'COORDINATOR',
        ]);

        CoursesTeachersModel::factory()->create([
            'course_uid' => $courseUid,
            'user_uid' => $secondUserUid,
            'type' => 'NO_COORDINATOR',
        ]);
    }

    private function addBlocks($courseUid)
    {
        $blocks = rand(1, 5);
        for ($i = 0; $i < $blocks; $i++) {
            $blockUid = generateUuid();
            BlocksModel::factory()->create([
                'uid' => $blockUid,
                'course_uid' => $courseUid,
                'order' => $i,
            ]);

            $this->addSubblocks($blockUid);
            $this->addBlockLearningResults($blockUid);
        }
    }

    private function addSubblocks($blockUid)
    {
        $subblocks = rand(1, 5);
        for ($i = 0; $i < $subblocks; $i++) {
            $subBlockUid = generateUuid();
            SubblocksModel::factory()->create([
                'uid' => $subBlockUid,
                'block_uid' => $blockUid,
                'order' => $i,
            ]);
            $this->addElements($subBlockUid);
        }
    }

    private function addElements($subBlockUid)
    {
        $elements = rand(1, 5);
        for ($i = 0; $i < $elements; $i++) {
            $elementUid = generateUuid();
            ElementsModel::factory()->create([
                'uid' => $elementUid,
                'subblock_uid' => $subBlockUid,
                'order' => $i,
            ]);

            $this->addSubelements($elementUid);
        }
    }

    private function addSubelements($elementUid)
    {
        $subelements = rand(1, 5);
        for ($i = 0; $i < $subelements; $i++) {
            SubelementsModel::factory()->create([
                'element_uid' => $elementUid,
                'order' => $i,
            ]);
        }
    }

    private function addBlockLearningResults($blockUid)
    {
        $learningResults = $this->learningResults->random(3);
        foreach ($learningResults as $learningResult) {
            LearningResultsBlocksModel::factory()->create([
                'course_block_uid' => $blockUid,
                'learning_result_uid' => $learningResult,
            ]);
        }
    }

    private function addStudents($courseUid, $statusCourse)
    {
        $students = [];
        if ($statusCourse == "INSCRIPTION") {
            $students[] = $this->students->random();
        }
        else {
            $students = $this->students->pluck("uid");
        }

        foreach ($students as $student) {
            $statusStudent = $statusCourse == "INSCRIPTION" ? "INSCRIBED" : $this->faker->randomElement(['ENROLLED', 'INSCRIBED']);
            $acceptanceStatus = $this->faker->randomElement(['ACCEPTED', 'PENDING', 'REJECTED']);

            CoursesStudentsModel::factory()->create([
                'course_uid' => $courseUid,
                'user_uid' => $student,
                'status' => $statusStudent,
                'acceptance_status' => $acceptanceStatus,
            ]);
        }

        return $students;
    }

    private function addStudentsDocumentsCourse($documentsUids, $studentsUids)
    {
        foreach ($studentsUids as $studentUid) {
            $randomNumberDocuments = rand(1, count($documentsUids));

            for ($i = 0; $i < $randomNumberDocuments; $i++) {
                CoursesStudentsDocumentsModel::factory()->create([
                    'user_uid' => $studentUid,
                    'course_document_uid' => $documentsUids[$i],
                    'document_path' => $this->demoDocuments[array_rand($this->demoDocuments)],
                ]);
            }
        }
    }


    private function addPaymentTerms($courseUid, $startDate, $finishDate)
    {
        $paymentTerms = rand(1, 5);
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($finishDate);
        $interval = ($endTimestamp - $startTimestamp) / $paymentTerms;

        for ($i = 0; $i < $paymentTerms; $i++) {
            $intervalStart = $startTimestamp + ($i * $interval);
            $intervalEnd = $intervalStart + $interval;

            $startDate = date('Y-m-d H:i', $intervalStart);
            $finishDate = date('Y-m-d H:i', $intervalEnd);
            CoursesPaymentTermsModel::factory()->create([
                'course_uid' => $courseUid,
                'start_date' => $startDate,
                'finish_date' => $finishDate,
            ]);
        }
    }

    private function addDocuments($courseUid)
    {
        $randomNumberDocuments = rand(1, 3);

        $documentsAddedUids = [];
        for ($i = 0; $i < $randomNumberDocuments; $i++) {
            $documentUid = generateUuid();
            $documentsAddedUids[] = $documentUid;
            CourseDocumentsModel::factory()->create([
                'uid' => $documentUid,
                'course_uid' => $courseUid,
            ]);
        }

        return $documentsAddedUids;
    }

    private function AddCourseAccesses($courseUid, $studentsUids) {

        foreach ($studentsUids as $student) {
            $accesses = rand(1, 1000);
            for ($i = 0; $i < $accesses; $i++) {
                CoursesAccesesModel::factory()->create([
                    'course_uid' => $courseUid,
                    'user_uid' => $student,
                    'access_date' => $this->faker->dateTimeBetween("-4 months", "-1 month"),
                ]);
            }
        }
    }
    private function AddCourseVisits($courseUid,$studentsUids){
        foreach ($studentsUids as $student) {
            $visits = rand(1, 1000);
            for ($i = 0; $i < $visits; $i++) {
                CoursesVisitsModel::factory()->create([
                    'course_uid' => $courseUid,
                    'user_uid' => $student,
                    'access_date' => $this->faker->dateTimeBetween("-4 months", "now"),
                ]);
            }
        }

    }
}
