<?php

namespace App\Http\Controllers\LearningObjects;

use App\Exceptions\OperationFailedException;
use App\Http\Controllers\Logs\LogsController;
use App\Jobs\SendChangeStatusCourseNotification;
use App\Jobs\SendCourseNotificationToManagements;
use App\Jobs\SendUpdateEnrollmentUserCourseNotification;
use App\Models\BlocksModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CoursesModel;
use App\Models\CallsModel;
use App\Models\CourseTypesModel;
use App\Models\UsersModel;
use App\Models\CategoriesModel;
use App\Models\CentersModel;
use App\Models\CertificationTypesModel;
use App\Models\CompetenceFrameworksModel;
use App\Models\CourseCategoriesModel;
use App\Models\CourseGlobalCalificationsModel;
use App\Models\CourseLearningResultCalificationsModel;
use App\Models\CoursesBlocksLearningResultsCalificationsModel;
use App\Models\CoursesEmailsContactsModel;
use App\Models\CoursesEmbeddingsModel;
use App\Models\CoursesPaymentTermsModel;
use App\Models\CoursesStudentDocumentsModel;
use App\Models\CoursesStudentsModel;
use App\Models\CoursesTagsModel;

use Illuminate\Support\Facades\DB;

use App\Models\CourseStatusesModel;
use App\Models\EducationalProgramsModel;
use App\Models\ElementsModel;
use App\Models\LmsSystemsModel;
use App\Models\SubblocksModel;
use App\Models\SubelementsModel;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use League\Csv\Reader;

use App\Rules\NifNie;
use App\Services\CertidigitalService;
use App\Services\KafkaService;
use App\Services\EmbeddingsService;
use DateTime;

class CoursesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $embeddingsService;
    protected $certidigitalService;

    public function __construct(EmbeddingsService $embeddingsService, CertidigitalService $certidigitalService)
    {
        $this->embeddingsService = $embeddingsService;
        $this->certidigitalService = $certidigitalService;
    }

    public function index()
    {
        $courses = CoursesModel::with('status')->get()->toArray();
        $calls = CallsModel::get()->toArray();
        $coursesStatuses = CourseStatusesModel::all()->toArray();
        $coursesTypes = CourseTypesModel::all()->toArray();
        $centers = CentersModel::all()->toArray();

        $teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->with('roles')->get()->toArray();

        $students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })->with('roles')->get()->toArray();

        $categories = CategoriesModel::with('parentCategory')->get()->toArray();

        $educationalPrograms = EducationalProgramsModel::all()->toArray();

        $lmsSystems = LmsSystemsModel::all();
        $certificationTypes = CertificationTypesModel::all();

        if (!empty($categories)) {
            $categories = $this->buildNestedCategories($categories);
        }

        $rolesUser = Auth::user()['roles']->pluck("code")->toArray();
        $variablesJs = [
            "frontUrl" => env('FRONT_URL'),
            "rolesUser" => $rolesUser,
            "enabledRecommendationModule" => app('general_options')['enabled_recommendation_module'],
        ];

        return view(
            'learning_objects.courses.index',
            [
                "page_name" => "Cursos",
                "page_title" => "Cursos",
                "resources" => [
                    "resources/js/learning_objects_module/courses.js"
                ],
                "courses" => $courses,
                "calls" => $calls,
                "courses_statuses" => $coursesStatuses,
                "courses_types" => $coursesTypes,
                "teachers" => $teachers,
                "categories" => $categories,
                "students" => $students,
                "lmsSystems" => $lmsSystems,
                "tabulator" => true,
                "tomselect" => true,
                "flatpickr" => true,
                "educational_programs" => $educationalPrograms,
                "variables_js" => $variablesJs,
                "treeselect" => true,
                "centers" => $centers,
                "certificationTypes" => $certificationTypes,
                "coloris" => true,
                "submenuselected" => "courses",
                "infiniteTree" => true
            ]
        );
    }


    /**
     * Construye un array de categorías anidadas, listo para usar en un select.
     *
     * @param array $categories El array original de categorías.
     * @param string|null $parent El UID de la categoría padre actual (inicialmente null).
     * @param string $prefix Los guiones para indicar el nivel de anidación (inicialmente vacío).
     * @param string $indicator El indicador de anidación.
     * @return array El array de categorías anidadas.
     */
    private function buildNestedCategories($categories, $parent = null, $prefix = '', $indicator = '')
    {
        $nested = []; // Este array contendrá las categorías anidadas.

        // Recorre cada categoría en el array original.
        foreach ($categories as $category) {

            // Comprueba si la categoría actual es un "hijo" del "padre" actual.
            if ($parent === ($category['parent_category']['uid'] ?? null)) {

                // Construye la opción con los guiones correspondientes para indicar el nivel de anidación.
                $nestedOption = $prefix . $indicator . $category['name'];

                // Agrega la categoría al array $nested.
                $nested[] = ['uid' => $category['uid'], 'name' => $nestedOption];

                // Llama a la función de forma recursiva para buscar más niveles de anidación,
                // y los combina con el array $nested.
                $nested = array_merge($nested, $this->buildNestedCategories($categories, $category['uid'], $prefix . $indicator));
            }
        }

        return $nested; // Devuelve el array $nested.
    }

    /**
     * Cambia el estado a un array de cursos
     */
    public function changeStatusesCourses(Request $request)
    {

        $changesCoursesStatuses = $request->input('changesCoursesStatuses');

        if (!$changesCoursesStatuses) {
            throw new OperationFailedException('No se han enviado los datos correctamente', 406);
        }

        // Obtenemos los cursos de la base de datos
        $coursesBd = CoursesModel::whereIn('uid', array_column($changesCoursesStatuses, "uid"))->with('status', 'lmsSystem')->get()->keyBy('uid');

        // Excluímos los estados a los que no se pueden cambiar manualmente.
        $statusesCourses = CourseStatusesModel::whereNotIn('code', ['DEVELOPMENT', 'PENDING_INSCRIPTION', 'FINISHED'])->get()->keyBy('code');
        // Aquí iremos almacenando los datos de los cursos que se van a actualizar

        DB::transaction(function () use ($changesCoursesStatuses, $coursesBd, $statusesCourses) {
            // Recorremos los cursos que nos vienen en el request y los comparamos con los de la base de datos
            foreach ($changesCoursesStatuses as $changeCourseStatus) {
                // Obtenemos el curso de la base de datos
                $course = $coursesBd[$changeCourseStatus['uid']] ?? null;
                $status = $statusesCourses[$changeCourseStatus['status']] ?? null;
                $reason = $changeCourseStatus['reason'];

                // Si no existe el curso en la base de datos, devolvemos un error
                if (!$course) {
                    throw new OperationFailedException("Uno de los cursos no existe", 406);
                }

                if (!$status) {
                    throw new OperationFailedException("El estado es incorrecto", 406);
                }

                $this->updateStatusCourse($course, $status, $reason);

                if ($status->code == "ACCEPTED_PUBLICATION" && !$course->lms_url) {
                    $this->sendNotificationCourseAcceptedPublicationToKafka($course, $course->lmsSystem->identifier);
                }

                dispatch(new SendChangeStatusCourseNotification($course));
            }

            LogsController::createLog('Cambio de estado de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Se han actualizado los estados de los cursos correctamente'], 200);
    }

    public function emitAllCredentials(Request $request)
    {
        $courseUid = $request->input('course_uid');

        $course = CoursesModel::where('uid', $courseUid)->with("students")->first();

        $studentsUids = $course->students->pluck('uid')->toArray();
        $this->certidigitalService->emissionCredentialsCourse($courseUid, $studentsUids);

        return response()->json(['message' => 'Credenciales emitidas correctamente'], 200);
    }

    public function emitCredentials(Request $request)
    {

        $courseUid = $request->input('course_uid');
        $studentsUids = $request->input('students_uids');

        $users = UsersModel::whereIn('uid', $studentsUids)->get();
        // Comprobar si alguno de los alumnos no está verificado
        foreach ($users as $user) {
            if (!$user->verified) {
                throw new OperationFailedException('No se pueden emitir credenciales porque alguno de los alumnos no está verificado', 422);
            }
        }

        // Comprobación de si alguno de los alumnos ya tiene las credenciales emitidas
        $this->checkCredentialsStudentsEmissionsInCourse($courseUid, $studentsUids);

        $this->certidigitalService->emissionCredentialsCourse($courseUid, $studentsUids);

        return response()->json(['message' => 'Credenciales emitidas correctamente'], 200);
    }

    public function sendCredential(Request $request)
    {
        $courseUid = $request->input('course_uid');
        $studentsUids = $request->input('students_uids');

        $this->certidigitalService->sendCourseCredentials([$courseUid], $studentsUids);

        return response()->json(['message' => 'Credenciales enviadas correctamente'], 200);
    }

    public function sealCredential(Request $request)
    {
        $courseUid = $request->input('course_uid');
        $studentsUids = $request->input('students_uids');

        $this->certidigitalService->sealCoursesCredentials([$courseUid], $studentsUids);

        return response()->json(['message' => 'Credenciales selladas correctamente'], 200);
    }

    public function regenerateStudentCredentials(Request $request)
    {
        $courseUid = $request->input('course_uid');

        $this->certidigitalService->createUpdateCourseCredential($courseUid);

        return response()->json(['message' => 'Credencial de estudiante regenerada correctamente'], 200);
    }

    public function regenerateTeacherCredentials(Request $request)
    {
        $courseUid = $request->input('course_uid');

        $this->certidigitalService->createUpdateCourseTeacherCredential($courseUid);

        return response()->json(['message' => 'Credencial de docente regenerada correctamente'], 200);
    }

    private function checkCredentialsStudentsEmissionsInCourse($courseUid, $studentsUids)
    {
        $coursesStudentsWithEmissions = CoursesStudentsModel::where('course_uid', $courseUid)
            ->whereIn('user_uid', $studentsUids)
            ->where('emissions_block_uuid', "!=", null)
            ->exists();

        if ($coursesStudentsWithEmissions) {
            throw new OperationFailedException('No se pueden emitir credenciales porque alguno de los alumnos ya tiene credenciales emitidas', 422);
        }
    }

    public function regenerateEmbeddings(Request $request)
    {
        $coursesUids = $request->input('courses_uids');
        $courses = CoursesModel::whereIn('uid', $coursesUids)->get();

        foreach ($courses as $course) {
            $this->embeddingsService->generateEmbeddingForCourse($course);
        }

        return response()->json(['message' => 'Se han regenerado los embeddings correctamente'], 200);
    }

    public function sendCredentials(Request $request)
    {
        $courseUid = $request->input('course_uid');
        $this->certidigitalService->emissionCredentialsCourse($courseUid);

        return response()->json(['message' => 'Se han enviado las credenciales correctamente'], 200);
    }

    private function updateStatusCourse($course, $status, $reason)
    {
        $course->course_status_uid = $status->uid;
        $course->status_reason = $reason;
        $course->save();
    }

    private function sendNotificationCourseAcceptedPublicationToKafka($course, $lmsCode)
    {
        $courseData = [
            'course_uid' => $course->uid,
            'title' => $course->title,
            "description" => $course->description,
            'realization_start_date' => $course->realization_start_date,
            'realization_finish_date' => $course->realization_start_date,
        ];

        $kafkaService = new KafkaService();
        $kafkaService->sendMessage('course_accepted_publication', $courseData, $lmsCode);
    }

    public function getCourses(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $filters = $request->get('filters');

        // Si es administrador o gestor, visualizará todos los cursos. Si es profesor,
        // solo los creados por él o en los que esté asignado
        $roles = Auth::user()['roles']->toArray();

        $roles = array_column($roles, "code");

        $query = CoursesModel::query();

        $roles = array_column(Auth::user()['roles']->toArray(), "code");

        // Si es un administrador o gestor, ve todos los cursos
        // Si es un profesor, solo ve los cursos en los que está asignado
        if (in_array('ADMINISTRATOR', $roles) || in_array('MANAGEMENT', $roles)) {
            $query = $this->buildQueryCoursesBase();
        } elseif (in_array("TEACHER", $roles)) {
            $query = $this->buildQueryForTeacher();
        }

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('courses.identifier', $search);
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                if ($order['field'] == 'certidigital_credential_uid') {
                    $query->orderByRaw('CASE WHEN courses.certidigital_credential_uid IS NULL THEN 0 ELSE 1 END, courses.certidigital_credential_uid ' . $order['dir']);
                } elseif ($order['field'] == 'certidigital_teacher_credential_uid') {
                    $query->orderByRaw('CASE WHEN courses.certidigital_teacher_credential_uid IS NULL THEN 0 ELSE 1 END, courses.certidigital_teacher_credential_uid ' . $order['dir']);
                } else {
                    $query->orderBy($order['field'], $order['dir']);
                }
            }
        }

        if ($filters) {
            $this->applyFilters($filters, $query);
        }

        $data = $query->paginate($size);

        $dates = [
            'inscription_start_date',
            'inscription_finish_date',
            'realization_start_date',
            'realization_finish_date',
            'enrolling_start_date',
            'enrolling_finish_date'
        ];

        adaptDatesModel($data, $dates, true);

        return response()->json($data, 200);
    }

    private function buildQueryCoursesBase()
    {
        return CoursesModel::query()
            ->leftJoin('course_statuses as status', 'courses.course_status_uid', '=', 'status.uid')
            ->leftJoin('calls as calls', 'courses.call_uid', '=', 'calls.uid')
            ->leftJoin('educational_programs as educational_programs', 'courses.educational_program_uid', '=', 'educational_programs.uid')
            ->leftJoin('course_types as course_types', 'courses.course_type_uid', '=', 'course_types.uid')
            ->leftJoin('centers as centers', 'courses.center_uid', '=', 'centers.uid')
            ->with('tags')
            ->with('contact_emails')
            ->with('teachers_coordinate')
            ->with('teachers_no_coordinate')
            ->with('categories')
            ->leftJoin('courses_embeddings', 'courses.uid', '=', 'courses_embeddings.course_uid')
            ->select(
                'courses.*',
                'status.name as status_name',
                'status.code as status_code',
                'calls.name as calls_name',
                'educational_programs.name as educational_programs_name',
                'course_types.name as course_types_name',
                'centers.name as centers_name',
            )
            ->addSelect(DB::raw('CASE WHEN courses_embeddings.embeddings IS NULL THEN 0 ELSE 1 END as embeddings_status'));
    }

    private function buildQueryForTeacher()
    {
        $userUid = Auth::user()['uid'];

        $queryCoursesBase = $this->buildQueryCoursesBase();

        return $queryCoursesBase->where(function ($query) use ($userUid) {
            $query->where('courses.creator_user_uid', '=', $userUid)
                ->orWhereHas('teachers_coordinate', function ($query) use ($userUid) {
                    $query->where('user_uid', '=', $userUid);
                })
                ->orWhereHas('teachers_no_coordinate', function ($query) use ($userUid) {
                    $query->where('user_uid', '=', $userUid);
                });
        });
    }

    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == "center") {
                $query->where("center", 'ILIKE', "%{$filter['value']}%");
            } elseif ($filter['database_field'] == 'inscription_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('courses.inscription_start_date', '<=', $filter['value'][1])
                        ->where('courses.inscription_finish_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('courses.inscription_start_date', '<=', $filter['value'])
                        ->whereDate('courses.inscription_finish_date', '>=', $filter['value']);
                }
            } elseif ($filter['database_field'] == 'realization_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('courses.realization_start_date', '<=', $filter['value'][1])
                        ->where('courses.realization_finish_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('courses.realization_start_date', '<=', $filter['value'])
                        ->whereDate('courses.realization_finish_date', '>=', $filter['value']);
                }
            } elseif ($filter['database_field'] == "coordinators_teachers") {
                $teachersUids = $filter['value'];
                $query->whereHas('teachers', function ($query) use ($teachersUids) {
                    $query->whereIn('users.uid', $teachersUids)
                        ->where('type', 'COORDINATOR');
                });
            } elseif ($filter['database_field'] == "no_coordinators_teachers") {
                $teachersUids = $filter['value'];
                $query->whereHas('teachers', function ($query) use ($teachersUids) {
                    $query->whereIn('users.uid', $teachersUids)
                        ->where('type', 'NO_COORDINATOR');
                });
            } elseif ($filter['database_field'] == 'creator_user_uid') {
                $query->whereIn('courses.creator_user_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'categories') {
                $categoriesUids = $filter['value'];
                $query->whereHas('categories', function ($query) use ($categoriesUids) {
                    $query->whereIn('categories.uid', $categoriesUids);
                });
            } elseif ($filter['database_field'] == 'course_statuses') {
                $query->whereIn('course_status_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'calls') {
                $query->whereIn('courses.call_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'course_types') {
                $query->whereIn('course_type_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'min_ects_workload') {
                $query->where('ects_workload', '>=', $filter['value']);
            } elseif ($filter['database_field'] == 'max_ects_workload') {
                $query->where('ects_workload', '<=', $filter['value']);
            } elseif ($filter['database_field'] == 'min_cost') {
                $query->where('courses.cost', '>=', $filter['value']);
            } elseif ($filter['database_field'] == 'max_cost') {
                $query->where('courses.cost', '<=', $filter['value']);
            } elseif ($filter['database_field'] == 'min_required_students') {
                $query->where('courses.min_required_students', '>=', $filter['value']);
            } elseif ($filter['database_field'] == 'max_required_students') {
                $query->where('courses.min_required_students', '<=', $filter['value']);
            } elseif ($filter['database_field'] == 'validate_student_registrations') {
                $query->where('courses.validate_student_registrations', $filter['value']);
            } elseif ($filter['database_field'] == 'learning_results') {
                $query->with([
                    'blocks.learningResults'
                ])->whereHas('blocks.learningResults', function ($query) use ($filter) {
                    $query->whereIn('learning_results.uid', $filter['value']);
                });
            } elseif ($filter['database_field'] == "embeddings") {
                $query->where(DB::raw('CASE WHEN courses_embeddings.embeddings IS NULL THEN 0 ELSE 1 END'), '=', $filter['value']);
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }

    /**
     * Obtiene un curso por uid
     */

    public function getCourse($courseUid)
    {
        $course = CoursesModel::where('uid', $courseUid)->with([
            'status',
            'teachers',
            'categories',
            'tags',
            'creatorUser',
            'courseDocuments',
            'educational_program',
            'educational_program.status',
            'blocks' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'blocks.competences',
            'blocks.learningResults',
            'blocks.subBlocks' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'blocks.subBlocks.elements' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'blocks.subBlocks.elements.subElements' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'contact_emails',
            'center',
            'paymentTerms'
        ])
            ->first();

        $dates = [
            'inscription_start_date',
            'inscription_finish_date',
            'realization_start_date',
            'realization_finish_date',
            'enrolling_start_date',
            'enrolling_finish_date'
        ];

        adaptDatesModel($course, $dates, false);

        if (!$course) {
            return response()->json(['message' => 'El curso no existe'], 406);
        }

        return response()->json($course, 200);
    }

    /**
     * Guarda un curso. Si el uid es null, crea un nuevo curso, si no, lo actualiza
     */
    public function saveCourse(Request $request)
    {
        adaptRequestDatesToUTC($request);

        $courseUid = $request->input('course_uid');
        $courseBd = $this->getCourseBd($courseUid);

        $errors = $this->validateCourseFields($request);
        if (!$errors->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errors], 422);
        }

        $structure = json_decode($request->input('structure'), true);
        $this->validateCourseStructure($structure);

        $action = $request->input('action');
        $belongsEducationalProgram = $request->input('belongs_to_educational_program');
        $newCourseStatus = $this->determineCourseStatus($action, $courseBd, $belongsEducationalProgram);

        DB::transaction(function () use ($request, $courseBd, $belongsEducationalProgram, $newCourseStatus) {
            $this->processCourseTransaction($request, $courseBd, $belongsEducationalProgram, $newCourseStatus);
        }, 5);

        $isNew = !$courseUid;
        return response()->json(['message' => $isNew ? 'Se ha añadido el curso correctamente' : 'Se ha actualizado el curso correctamente'], 200);
    }

    private function getCourseBd($courseUid)
    {
        if ($courseUid) {
            $courseBd = CoursesModel::where('uid', $courseUid)->with([
                "educational_program",
                "embeddings",
                'blocks.learningResults',
            ])->first();
            $this->checkStatusCourse($courseBd);

            if ($courseBd->status->code == "ADDED_EDUCATIONAL_PROGRAM") {
                $this->checkRealizationDatesCourseAddEducationalProgram(request(), $courseBd);
            }
        } else {
            $courseBd = new CoursesModel();
            $courseBd->uid = generateUuid();
            $courseBd->identifier = $this->generateCourseIdentifier();
            $courseBd->creator_user_uid = Auth::user()['uid'];
        }

        return $courseBd;
    }

    private function determineCourseStatus($action, $courseBd, $belongsEducationalProgram)
    {
        if ($belongsEducationalProgram) {
            return $this->statusCourseBelongsEducationalProgram($action, $courseBd);
        } else {
            return $this->statusCourseNotBelongsEducationalProgram($action, $courseBd);
        }
    }

    private function processCourseTransaction($request, $courseBd, $belongsEducationalProgram, $newCourseStatus)
    {
        $courseBdCopy = clone $courseBd;
        $isManagement = Auth::user()->hasAnyRole(['MANAGEMENT']);
        $embeddings = $this->generateCourseEmbeddings($request, $courseBd);

        if ($newCourseStatus) {
            $courseBd->course_status_uid = $newCourseStatus->uid;
        }

        if ($courseBd->course_origin_uid && !$isManagement) {
            $this->updateCourseFieldsNewEdition($request, $courseBd);
            $courseBd->save();
            $this->handleCourseDocuments($request, $courseBd);
        } else {
            $this->updateCourseFields($request, $courseBd, $belongsEducationalProgram);
            $courseBd->save();
            $this->updateAuxiliarDataCourse($courseBd, $request);
        }

        $this->notifyManagementIfPendingApproval($newCourseStatus, $courseBd);
        $this->updateCourseImage($request, $courseBd);
        $this->saveLogMessageSaveCourse(!$courseBd->exists, $courseBd->title);
        $courseBd->save();

        if ($embeddings) {
            CoursesEmbeddingsModel::updateOrCreate(
                ['course_uid' => $courseBd->uid],
                ['embeddings' => $embeddings]
            );
        }

        $this->handleCoursePublication($request, $newCourseStatus, $courseBd);
        $this->updateCertidigitalCredentials($courseBdCopy, $courseBd);
    }

    private function handleCourseDocuments($request, $courseBd)
    {
        $validateStudentRegistrations = $request->input('validate_student_registrations');
        if ($validateStudentRegistrations) {
            $this->updateDocumentsCourse($request, $courseBd);
        } else {
            $courseBd->courseDocuments()->delete();
        }
    }

    private function notifyManagementIfPendingApproval($newCourseStatus, $courseBd)
    {
        if ($newCourseStatus && $newCourseStatus->code == "PENDING_APPROVAL") {
            dispatch(new SendCourseNotificationToManagements($courseBd->toArray()));
        }
    }

    private function updateCourseImage($request, $courseBd)
    {
        $imageFile = $request->file('image_input_file');
        if ($imageFile) {
            $this->updateImageField($imageFile, $courseBd);
        }
    }

    private function handleCoursePublication($request, $newCourseStatus, $courseBd)
    {
        if ($courseBd->lms_system_uid && $newCourseStatus && $newCourseStatus->code == "ACCEPTED_PUBLICATION" && !$courseBd->lms_url) {
            $lmsSystem = LmsSystemsModel::where('uid', $request->input('lms_system_uid'))->first();
            $this->sendNotificationCourseAcceptedPublicationToKafka($courseBd, $lmsSystem->identifier);
        }
    }

    private function updateCertidigitalCredentials($courseBdCopy, $courseBd)
    {
        $changesCourse = $this->detectChangesCredential($courseBdCopy, $courseBd);

        if (!$courseBd->certidigitalCredential || $changesCourse) {
            $this->certidigitalService->createUpdateCourseTeacherCredential($courseBd->uid);

            if (!$courseBd->belongs_to_educational_program) {
                $this->certidigitalService->createUpdateCourseCredential($courseBd->uid);
            } elseif ($courseBd->belongs_to_educational_program && $courseBd->educational_program && $courseBd->educational_program->certidigitalCredential) {
                $this->certidigitalService->createUpdateEducationalProgramCredential($courseBd->educational_program->uid);
            }
        }
    }

    private function detectChangesCredential($courseBeforeChanges, $courseAfterChanges)
    {
        $courseBeforeChanges->load('blocks.learningResults');
        if ($courseBeforeChanges->title != $courseAfterChanges->title) {
            return true;
        } elseif (json_encode($courseAfterChanges->blocks) != json_encode($courseBeforeChanges->blocks)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * Si el curso no tiene embeddings, se generan.
     * Si el título o la descripción han cambiado, se generan nuevos embeddings.
     * Si no se cumplen las condiciones anteriores o falla la API, se devuelven los embeddings actuales.
     */
    private function generateCourseEmbeddings($request, $courseBd)
    {
        $title = $request->input('title');
        $description = $request->input('description');

        if (!$courseBd->embeddings || $title != $courseBd->title || $description != $courseBd->description) {
            $embeddings = $this->embeddingsService->getEmbedding($title . ' ' . $description);
            return $embeddings ?: $courseBd->embeddings->embeddings ?? null;
        }

        return $courseBd->embeddings->embeddings;
    }

    private function checkRealizationDatesCourseAddEducationalProgram($request, $courseBd)
    {
        $realizationStartDate = $request->input('realization_start_date');
        $realizationFinishDate = $request->input('realization_finish_date');

        $educationalProgramStartDate = $courseBd->educational_program->realization_start_date;
        $educationalProgramFinishDate = $courseBd->educational_program->realization_finish_date;

        if ($realizationStartDate < $educationalProgramStartDate || $realizationFinishDate > $educationalProgramFinishDate) {
            throw new OperationFailedException('Las fechas de realización deben estar dentro del rango del programa formativo', 422);
        }
    }

    private function checkStatusCourse($courseBd)
    {
        $isUserManagement = Auth::user()->hasAnyRole(['MANAGEMENT']);

        // Si es gestor, siempre podrá editar el curso
        if ($isUserManagement) {
            return;
        }

        $statusesAllowEdit = ["INTRODUCTION", "UNDER_CORRECTION_APPROVAL", "UNDER_CORRECTION_PUBLICATION"];
        if (!in_array($courseBd->status->code, $statusesAllowEdit) && !$courseBd->belongs_to_educational_program) {
            throw new OperationFailedException('No puedes editar un curso que no esté en estado de introducción o subsanación', 422);
        } elseif ($courseBd->status->code == "ADDED_EDUCATIONAL_PROGRAM" && $courseBd->belongs_to_educational_program) {
            if (!in_array($courseBd->educational_program->status->code, $statusesAllowEdit)) {
                throw new OperationFailedException('No puedes editar un curso cuyo programa formativo no esté en estado de introducción o subsanación', 422);
            }
        }
    }

    public function getCourseCalifications(Request $request, $courseUid)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $course = CoursesModel::where("uid", $courseUid)->first();

        if ($course->belongs_to_educational_program) {
            $educationalProgramCourse = $course->educational_program()->first();
            $coursesStudentsQuery = $educationalProgramCourse->students();
        } else {
            $coursesStudentsQuery = $course->students();
        }

        // Añadir una subconsulta para la calificación del curso
        $coursesStudentsQuery->addSelect([
            'calification_info' => CourseGlobalCalificationsModel::selectRaw('calification_info')
                ->whereColumn('user_uid', 'users.uid')
                ->where('course_uid', $courseUid)
        ]);

        $coursesStudentsQuery->with([
            "courseBlocksLearningResultsCalifications",
            "courseBlocksLearningResultsCalifications.block" => function ($query) use ($courseUid) {
                return $query->where("course_uid", $courseUid);
            },
            "courseLearningResultCalifications" => function ($query) use ($courseUid) {
                return $query->where("course_uid", $courseUid);
            }
        ]);

        if ($search) {
            $coursesStudentsQuery->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw("concat(first_name, ' ', last_name) ILIKE ?", ["%$search%"])
                    ->orWhere('nif', 'ILIKE', "%$search%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $coursesStudentsQuery->orderBy($order['field'], $order['dir']);
            }
        }

        $coursesStudents = $coursesStudentsQuery->paginate($size);

        $courseBlocks = $course->blocks()->with("learningResults.competence.competenceFramework.levels")->get();

        // Resultados de aprendizaje únicos
        $learningResults = [];
        foreach ($courseBlocks as $block) {
            foreach ($block->learningResults as $learningResult) {
                $learningResults[$learningResult->uid] = $learningResult->toArray();
            }
        }

        // Convertir el array asociativo de vuelta a un array indexado
        $learningResults = array_values($learningResults);

        return response()->json([
            "coursesStudents" => $coursesStudents,
            "courseBlocks" => $courseBlocks,
            "learningResults" => $learningResults,
            "course" => $course
        ], 200);
    }

    public function saveCalification(Request $request, $courseUid)
    {
        $blocksLearningResultCalifications = $request->input("blocksLearningResultCalifications");
        $learningResultsCalifications = $request->input("learningResultsCalifications");
        $globalCalifications = $request->input("globalCalifications");

        $course = CoursesModel::where("uid", $courseUid)->with("educational_program")->first();

        DB::transaction(function () use ($blocksLearningResultCalifications, $learningResultsCalifications, $globalCalifications, $course) {
            foreach ($blocksLearningResultCalifications as $blockCalification) {
                CoursesBlocksLearningResultsCalificationsModel::updateOrCreate(
                    [
                        "user_uid" => $blockCalification["userUid"],
                        "course_block_uid" => $blockCalification["blockUid"],
                        "learning_result_uid" => $blockCalification["learningResultUid"],
                    ],
                    [
                        "uid" => generateUuid(),
                        "calification_info" => $blockCalification["calificationInfo"],
                        "competence_framework_level_uid" => $blockCalification["levelUid"]
                    ]
                );
            }

            foreach ($learningResultsCalifications as $learningResultCalification) {
                CourseLearningResultCalificationsModel::updateOrCreate(
                    [
                        "user_uid" => $learningResultCalification["userUid"],
                        "learning_result_uid" => $learningResultCalification["learningResultUid"],
                        "course_uid" => $course->uid
                    ],
                    [
                        "uid" => generateUuid(),
                        "calification_info" => $learningResultCalification["calificationInfo"],
                        "competence_framework_level_uid" => $learningResultCalification["levelUid"]
                    ]
                );
            }

            foreach ($globalCalifications as $globalCalification) {
                CourseGlobalCalificationsModel::updateOrCreate(
                    [
                        "user_uid" => $globalCalification["user_uid"],
                        "course_uid" => $course->uid
                    ],
                    [
                        "uid" => generateUuid(),
                        "calification_info" => $globalCalification["calification_info"]
                    ]
                );
            }
        });

        return response()->json(['message' => 'Se han guardado las calificaciones correctamente'], 200);
    }

    private function updateAuxiliarDataCourse($courseBd, $request)
    {

        $belongsEducationalProgram = $request->input('belongs_to_educational_program');
        if (!$belongsEducationalProgram) {
            // Tags
            $this->updateTagsCourse($request, $courseBd);

            // Categorías
            $this->updateCategoriesCourse($request, $courseBd);

            // Documentos
            $validateStudentRegistrations = $request->input('validate_student_registrations');
            if ($validateStudentRegistrations) {
                $this->updateDocumentsCourse($request, $courseBd);
            } else {
                $courseBd->courseDocuments()->delete();
            }

            // Plazos de pago
            $paymentMode = $request->input('payment_mode');
            if ($paymentMode == "INSTALLMENT_PAYMENT") {
                $this->updatePaymentTerms($request, $courseBd);
            } elseif ($paymentMode == "SINGLE_PAYMENT") {
                $courseBd->paymentTerms()->delete();
            }
        } else {
            $courseBd->categories()->detach();
            $courseBd->tags()->delete();
            $courseBd->courseDocuments()->delete();
            $courseBd->paymentTerms()->delete();
        }

        // Guardado de profesores
        $this->updateTeachers($request, $courseBd);

        // Estructura
        $structure = $request->input('structure');
        $structure = json_decode($structure, true);
        $this->syncStructure($structure, $courseBd->uid);

        // Emails de contacto
        $contactEmails = json_decode($request->input('contact_emails'), true);
        $this->syncItemsCourseEmails($contactEmails, $courseBd->uid);
    }

    private function updatePaymentTerms($request, $courseBd)
    {
        $paymentTerms = $request->input('payment_terms');
        $paymentTerms = json_decode($paymentTerms, true);
        $this->syncPaymentTerms($paymentTerms, $courseBd);
    }

    private function updateTagsCourse($request, $courseBd)
    {
        $tags = json_decode($request->input('tags'), true);
        $this->syncItemsTags($tags, $courseBd->uid);
    }

    private function updateCategoriesCourse($request, $courseBd)
    {
        $categories = $request->input('categories');
        $categories = json_decode($categories, true);
        $this->syncCategories($categories, $courseBd);
    }

    private function updateDocumentsCourse($request, $courseBd)
    {
        $documents = $request->input('documents');
        $documents = json_decode($documents, true);
        $courseBd->updateDocuments($documents);
    }

    private function validateCourseFields(Request $request)
    {
        $validatorMessages = $this->getValidatorCourseMessages();

        $rules = [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'contact_information' => 'nullable|string',
            'course_type_uid' => 'required|string',
            'min_required_students' => 'nullable|integer',
            'realization_start_date' => 'required',
            'realization_finish_date' => 'required',
            'presentation_video_url' => 'nullable|url',
            'objectives' => 'nullable|string',
            'ects_workload' => 'required|numeric',
            'validate_student_registrations' => 'required|boolean',
            'lms_url' => 'nullable|url',
            'lms_system_uid' => 'nullable|required_with:lms_url',
            'cost' => 'nullable|numeric',
            'featured_big_carrousel_title' => 'required_if:featured_big_carrousel,1',
            'featured_big_carrousel_description' => 'required_if:featured_big_carrousel,1',
            'featured_big_carrousel_image_path' => [
                function ($attribute, $value, $fail) use ($request) {
                    $featuredBigCarrousel = $request->input('featured_big_carrousel');
                    $courseUid = $request->input('course_uid');
                    if ($featuredBigCarrousel && !$courseUid && !$value) {
                        $fail('Debes subir una imagen destacada para el slider');
                    }
                },
            ],
            'featured_slider_color_font' => 'required_if:featured_big_carrousel,1',
            'evaluation_criteria' => 'required_if:validate_student_registrations,1',
            'teacher_no_coordinators' => [
                function ($attribute, $value, $fail) use ($request) {
                    $teacherCoordinators = json_decode($request->input('teacher_coordinators'), true);
                    $value = json_decode($value, true);
                    $duplicates = array_intersect($value, $teacherCoordinators);
                    if (!empty($duplicates)) {
                        $fail('No puede haber profesores que sean a la vez coordinadores y no coordinadores');
                    }
                },
            ],
            'min_required_students' => 'nullable|integer|min:0',
        ];

        if (app('general_options')['operation_by_calls']) {
            $rules['call_uid'] = 'required';
        }

        $belongsToEducationalProgram = $request->input('belongs_to_educational_program');

        $isCourseEdition = $request->input('course_origin_uid');
        if ($belongsToEducationalProgram && !$isCourseEdition) {
            $this->addRulesIfBelongsToEducationalProgram($rules);
        } else {
            $this->addRulesIfNotBelongsEducationalProgram($request, $rules);
        }

        $validator = Validator::make($request->all(), $rules, $validatorMessages);

        return $validator->errors();
    }

    private function addRulesIfNotBelongsEducationalProgram($request, &$rules)
    {

        $rules['inscription_start_date'] = 'required';
        $rules['inscription_finish_date'] = 'required|after_or_equal:inscription_start_date';

        $validateStudentRegistrations = $request->input('validate_student_registrations');
        $paymentMode = $request->input('payment_mode');

        if ($validateStudentRegistrations) {
            $rules['enrolling_start_date'] = 'required|after_or_equal:inscription_finish_date';
            $rules['enrolling_finish_date'] = 'required|after_or_equal:enrolling_start_date';

            $rules['realization_start_date'] = 'required|after_or_equal:enrolling_finish_date';
            $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
        } else {
            $rules['realization_start_date'] = 'required|after_or_equal:inscription_finish_date';
            $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
        }

        if ($paymentMode == "INSTALLMENT_PAYMENT") {
            $rules['payment_terms'] = [
                'required',
                function ($attribute, $value, $fail) {
                    $value = json_decode($value, true);
                    $validation = $this->validatePaymentTerms($value);
                    if ($validation !== true) {
                        $fail($validation);
                    }
                },
            ];
        }
    }

    public function calculateMedianEnrollingsCategories(Request $request)
    {
        $categoriesUids = $request->input("categories_uids");
        $median = $this->getMedianInscribedCategories($categoriesUids);
        return response()->json(['median' => $median], 200);
    }

    private function getMedianInscribedCategories($categoriesUids)
    {
        $courses = CoursesModel::withCount([
            "students" => function ($query) {
                return $query->where("status", "ENROLLED")->where("acceptance_status", "ACCEPTED");
            }
        ])
            ->whereHas("categories", function ($query) use ($categoriesUids) {
                $query->whereIn("categories.uid", $categoriesUids);
            })
            ->whereHas("status", function ($query) {
                $query->where("code", "FINISHED");
            })
            ->get();

        $studentCounts = $courses->pluck('students_count');

        return calculateMedian($studentCounts->toArray());
    }

    private function addRulesIfBelongsToEducationalProgram(&$rules)
    {
        $rules['realization_start_date'] = 'required';
        $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
    }

    private function validateCourseStructure($structure)
    {

        foreach ($structure as $block) {
            // Validamos si el bloque tiene más de 100 resultados
            if (count($block['learningResults']) > 100) {
                throw new OperationFailedException('No puedes añadir más de 100 resultados de aprendizaje por bloque', 422);
            }
        }
    }

    private function saveLogMessageSaveCourse($isNew, $courseTitle)
    {
        $logMessage = $isNew ? 'Curso añadido: ' : 'Curso actualizado: ';
        $logMessage .= $courseTitle;

        LogsController::createLog($logMessage, 'Cursos', auth()->user()->uid);
    }

    private function getValidatorCourseMessages()
    {

        return [
            'title.required' => 'Introduce el título del curso.',
            'course_type_uid.required' => 'Selecciona el tipo de curso.',
            'min_required_students.integer' => 'El número mínimo de estudiantes debe ser un número entero.',

            'inscription_start_date.required' => 'La fecha de inicio de inscripción es obligatoria.',
            'inscription_finish_date.required' => 'La fecha de fin de inscripción es obligatoria.',
            'inscription_start_date.after_or_equal' => 'La fecha de inicio de inscripción no puede ser anterior a la actual.',
            'inscription_finish_date.after_or_equal' => 'La fecha de fin de inscripción no puede ser anterior a la de inicio.',

            'realization_start_date.required' => 'La fecha de inicio de realización es obligatoria.',
            'realization_finish_date.required' => 'La fecha de finalización de realización es obligatoria.',
            'realization_finish_date.date_format' => 'La fecha de finalización de realización no tiene el formato correcto.',

            'presentation_video_url.url' => 'Introduce una URL válida para el video de presentación.',
            'ects_workload.required' => 'La carga de trabajo ECTS es obligatoria.',
            'ects_workload.numeric' => 'La carga de trabajo ECTS debe ser un número.',
            'validate_student_registrations.required' => 'Indica si se validarán las inscripciones de los estudiantes.',
            'lms_url.url' => 'Introduce una URL válida para el LMS.',
            'lms_system_uid.required_with' => 'El LMS es obligatorio cuando se proporciona la URL del LMS.',
            'call_uid.required' => 'Selecciona la convocatoria del curso.',
            'featured_big_carrousel_title.required_if' => 'Debes especificar un título',
            'featured_big_carrousel_description.required_if' => 'Debes especificar una descripción',
            'featured_slider_color_font.required_if' => 'Debes especificar un color para la fuente',
            'evaluation_criteria.required_if' => 'Debes especificar unos criterios de evaluación si activas la validación de estudiantes',
            'realization_start_date.after_or_equal' => 'La fecha de inicio de realización no puede ser anterior a la fecha de fin de inscripción.',
            'realization_finish_date.after_or_equal' => 'La fecha de finalización de realización no puede ser anterior a la fecha de inicio de realización.',
            'min_required_students.min' => 'El número mínimo de estudiantes no puede ser negativo.',
            'enrolling_start_date.required' => 'La fecha de inicio de matriculación es obligatoria.',
            'enrolling_finish_date.required' => 'La fecha de fin de matriculación es obligatoria.',
            'enrolling_start_date.after_or_equal' => 'La fecha de inicio de matriculación no puede ser anterior a la de fin de inscripción.',
            'enrolling_finish_date.after_or_equal' => 'La fecha de fin de matriculación no puede ser anterior a la de inicio de matriculación.',
            'featured_big_carrousel_image_path.required_if' => 'Debes seleccionar una imagen para el carrusel grande',
            'featured_slider_color_font.required_if' => 'Debes especificar un color de fuente para el carrousel'
        ];
    }

    // Validación del bloque de plazos de pago
    private function validatePaymentTerms($paymentTerms)
    {
        $fields = ['name', 'start_date', 'finish_date', 'cost'];

        if (!count($paymentTerms)) {
            return "Debes especificar al menos un plazo de pago";
        }

        $previousFinishDate = null;

        foreach ($paymentTerms as $paymentTerm) {
            if (!$paymentTerm["cost"]) {
                return "Debes especificar un coste para cada plazo de pago";
            }
            if ($paymentTerm['cost'] <= 0) {
                return "El coste de los plazos de pago no puede ser negativo";
            }
            if (!$paymentTerm['name']) {
                return "Debes especificar un nombre para el plazo de pago";
            }

            // Comprobamos si le falta algún campo
            foreach ($fields as $field) {
                if (!array_key_exists($field, $paymentTerm)) {
                    return "Falta el campo $field en uno de los plazos de pago";
                }
            }

            // Convertimos las fechas a objetos DateTime para compararlas
            $startDate = new DateTime($paymentTerm['start_date']);
            $finishDate = new DateTime($paymentTerm['finish_date']);

            if ($previousFinishDate && $startDate < $previousFinishDate) {
                return "La fecha de inicio de un plazo de pago debe ser posterior a la fecha de finalización del plazo de pago anterior";
            }

            if ($startDate > $finishDate) {
                return "La fecha de inicio de un plazo de pago no puede ser posterior a la fecha de finalización";
            }

            $previousFinishDate = $finishDate;
        }

        return true;
    }

    private function statusCourseBelongsEducationalProgram($action, $courseBd)
    {

        if ($courseBd->status && $courseBd->status->code == "ADDED_EDUCATIONAL_PROGRAM") {
            return null;
        }
        $statuses = CourseStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'READY_ADD_EDUCATIONAL_PROGRAM'
        ])->get()->keyBy('code');

        if ($action === "submit") {
            return $statuses['READY_ADD_EDUCATIONAL_PROGRAM'];
        } elseif ($action === "draft") {
            return $statuses['INTRODUCTION'];
        }
    }

    // En función de la acción y del estado actual del curso, se establece el nuevo estado
    private function statusCourseNotBelongsEducationalProgram($action, $courseBd)
    {
        $isUserManagement = Auth::user()->hasAnyRole(['MANAGEMENT']);

        $actualStatusCourse = $courseBd->status->code ?? null;

        $necessaryApprovalEditions = app('general_options')['necessary_approval_editions'];

        if ($isUserManagement || ($courseBd->course_origin_uid && !$necessaryApprovalEditions)) {
            return $this->statusCourseNotBelongsEducationalProgramUserManagementOrEdition($action, $actualStatusCourse);
        } else {
            return $this->statusCourseNotBelongsEducationalProgramUserTeacher($action, $actualStatusCourse);
        }
    }

    private function statusCourseNotBelongsEducationalProgramUserManagementOrEdition($action, $actualStatusCourse)
    {
        $statuses = CourseStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'ACCEPTED_PUBLICATION'
        ])->get()->keyBy('code');

        if ($action === "submit" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION")) {
            return $statuses['ACCEPTED_PUBLICATION'];
        } elseif ($action === "draft" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION")) {
            return $statuses['INTRODUCTION'];
        } else {
            return null;
        }
    }

    private function statusCourseNotBelongsEducationalProgramUserTeacher($action, $actualStatusCourse)
    {
        $statuses = CourseStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'PENDING_APPROVAL',
            'UNDER_CORRECTION_APPROVAL',
            'UNDER_CORRECTION_PUBLICATION',
            'PENDING_PUBLICATION'
        ])->get()->keyBy('code');

        if ($action === "submit") {
            if (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION") {
                return $statuses['PENDING_APPROVAL'];
            } elseif ($actualStatusCourse === "UNDER_CORRECTION_APPROVAL") {
                return $statuses['PENDING_APPROVAL'];
            } elseif ($actualStatusCourse === "UNDER_CORRECTION_PUBLICATION") {
                return $statuses['PENDING_PUBLICATION'];
            }
        } elseif ($action === "draft" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION")) {
            return $statuses['INTRODUCTION'];
        } else {
            return null;
        }
    }

    private function updateCourseFieldsNewEdition($request, $courseBd)
    {
        $fields = [
            "title",
            "inscription_start_date",
            "inscription_finish_date",
            "realization_start_date",
            "realization_finish_date",
            "min_required_students",
            "presentation_video_url",
            "lms_url",
            "lms_system_uid",
            "cost",
            "featured_big_carrousel",
            "featured_big_carrousel_title",
            "featured_big_carrousel_description",
            "featured_slider_color",
            "featured_small_carrousel",
            "validate_student_registrations",
            "evaluation_criteria",
            "call_uid",
            "certification_type_uid",
        ];

        $conditionalFields = ["enrolling_start_date", "enrolling_finish_date"];

        $validateStudentRegistrations = $request->input('validate_student_registrations');
        $cost = $request->input('cost');

        if ($validateStudentRegistrations || $cost && $cost > 0) {
            $fields = array_merge($fields, $conditionalFields);
        }

        $courseBd->fill($request->only($fields));

        // Establecer a null los campos que no están en la lista de campos a actualizar
        $allFields = array_merge($fields, $conditionalFields);
        foreach ($allFields as $field) {
            if (!in_array($field, $fields)) {
                $courseBd->$field = null;
            }
        }

        $imageFileBigCarrousel = $request->file('featured_big_carrousel_image_path');

        if ($imageFileBigCarrousel) {
            $courseBd->featured_big_carrousel_image_path = saveFile($imageFileBigCarrousel, 'images/carrousel-images', null, true);
        }
    }

    private function updateCourseFields($request, $courseBd, $belongsToEducationalProgram)
    {
        // Lista de todos los campos posibles
        $allFields = [
            'title',
            'description',
            'contact_information',
            'course_type_uid',
            'certification_type_uid',
            'call_uid',
            'center_uid',
            'objectives',
            'ects_workload',
            'lms_url',
            'lms_system_uid',
            'belongs_to_educational_program',
            'inscription_start_date',
            'inscription_finish_date',
            'realization_start_date',
            'realization_finish_date',
            'featured_big_carrousel_description',
            'featured_big_carrousel_title',
            'featured_slider_color_font',
            'presentation_video_url',
            'cost',
            'featured_big_carrousel',
            'enrolling_start_date',
            'enrolling_finish_date',
            'evaluation_criteria',
            'min_required_students',
            'validate_student_registrations',
            'featured_big_carrousel_image_path',
            'featured_small_carrousel',
            'payment_mode'
        ];
        if ($belongsToEducationalProgram) {
            $fields = [
                'title',
                'description',
                'contact_information',
                'course_type_uid',
                'certification_type_uid',
                'call_uid',
                'center_uid',
                'objectives',
                'ects_workload',
                'lms_url',
                'lms_system_uid',
                'belongs_to_educational_program',
                'realization_start_date',
                'realization_finish_date',
                'presentation_video_url',
                'payment_mode'
            ];
        } else {
            $fields = [
                'inscription_start_date',
                'inscription_finish_date',
                'realization_start_date',
                'realization_finish_date',
                'presentation_video_url',
                'featured_big_carrousel',
                'featured_big_carrousel_title',
                'featured_big_carrousel_description',
                'featured_slider_color',
                'featured_slider_color_font',
                'evaluation_criteria',
                'featured_small_carrousel',
                'belongs_to_educational_program',
                'title',
                'description',
                'contact_information',
                'course_type_uid',
                'certification_type_uid',
                'call_uid',
                'min_required_students',
                'center_uid',
                'objectives',
                'ects_workload',
                'featured_big_carrousel_image_path',
                'validate_student_registrations',
                'lms_url',
                'lms_system_uid',
                'payment_mode'
            ];

            $paymentMode = $request->input('payment_mode');
            $cost = $request->input('cost');
            $validateStudentRegistrations = $request->input('validate_student_registrations');

            if ($paymentMode == "SINGLE_PAYMENT") {
                array_push($fields, 'cost');
            }

            if (($paymentMode == "SINGLE_PAYMENT" && $cost > 0) || $validateStudentRegistrations) {
                array_push($fields, 'enrolling_start_date', 'enrolling_finish_date');
            }

            $imageFileBigCarrousel = $request->file('featured_big_carrousel_image_path');

            if ($imageFileBigCarrousel) {
                $courseBd->featured_big_carrousel_image_path = saveFile($imageFileBigCarrousel, 'images/carrousel-images', null, true);
            }
        }

        $courseBd->fill($request->only($fields));

        // Establecer a null los campos que no están en la lista de campos a actualizar
        foreach ($allFields as $field) {
            if (!in_array($field, $fields)) {
                $courseBd->$field = null;
            }
        }
    }

    private function updateImageField($imageFile, $courseBd)
    {
        $path = 'images/courses-images';
        $destinationPath = public_path($path);
        $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $imageFile->getClientOriginalExtension();
        $timestamp = time();

        $filename = "{$originalName}-{$timestamp}.{$extension}";

        $imageFile->move($destinationPath, $filename);

        $courseBd->image_path = $path . "/" . $filename;
    }

    private function updateTeachers($request, $courseBd)
    {
        $teachersNoCoordinators = json_decode($request->input('teacher_no_coordinators'));
        $teachersCoordinators = json_decode($request->input('teacher_coordinators'));

        // Combine both arrays and assign type
        $teachers = [];

        if ($teachersNoCoordinators && is_array($teachersNoCoordinators)) {
            foreach ($teachersNoCoordinators as $teacher) {
                $teachers[$teacher] = 'NO_COORDINATOR';
            }
        }

        if ($teachersCoordinators  && is_array($teachersCoordinators)) {
            foreach ($teachersCoordinators as $teacher) {
                $teachers[$teacher] = 'COORDINATOR';
            }
        }

        // Get current teachers of the course
        $teachersBd = UsersModel::whereIn('uid', array_keys($teachers))->get()->pluck('uid');

        // Prepare the array for teacher synchronization
        $teachersToSync = [];
        foreach ($teachersBd as $teacherUid) {
            $teachersToSync[$teacherUid] = [
                'uid' => generateUuid(),
                'course_uid' => $courseBd->uid,
                'user_uid' => $teacherUid,
                'type' => $teachers[$teacherUid]
            ];
        }

        $courseBd->teachers()->sync($teachersToSync);
    }

    private function syncCategories($categories, $courseBd)
    {
        $categoriesBd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

        CourseCategoriesModel::where('course_uid', $courseBd->uid)->delete();
        $categoriesToSync = [];

        foreach ($categoriesBd as $categoryUid) {
            $categoriesToSync[] = [
                'uid' => generateUuid(),
                'course_uid' => $courseBd->uid,
                'category_uid' => $categoryUid
            ];
        }

        $courseBd->categories()->sync($categoriesToSync);
    }

    private function syncItemsTags($items, $courseUid)
    {
        $currentTags = CoursesTagsModel::where('course_uid', $courseUid)->pluck('tag')->toArray();

        // Identificar qué items son nuevos y cuáles deben ser eliminados
        $itemsToAdd = array_diff($items, $currentTags);
        $itemsToDelete = array_diff($currentTags, $items);

        // Eliminar los items que ya no son necesarios
        CoursesTagsModel::where('course_uid', $courseUid)->whereIn('tag', $itemsToDelete)->delete();

        // Preparar el array para la inserción masiva de nuevos items
        $insertData = [];
        foreach ($itemsToAdd as $item) {
            $insertData[] = [
                'uid' => generateUuid(),
                'course_uid' => $courseUid,
                'tag' => $item
            ];
        }

        // Insertar todos los nuevos items en una única operación de BD
        CoursesTagsModel::insert($insertData);
    }

    private function syncPaymentTerms($paymentTerms, $courseBd)
    {
        $existingUids = $courseBd->paymentTerms()->pluck('uid')->toArray();

        $receivedUids = array_column($paymentTerms, 'uid');

        foreach ($paymentTerms as $paymentTerm) {
            if (in_array($paymentTerm['uid'], $existingUids)) {
                CoursesPaymentTermsModel::where('uid', $paymentTerm['uid'])->update([
                    'course_uid' => $courseBd->uid,
                    'name' => $paymentTerm['name'],
                    'start_date' => $paymentTerm['start_date'],
                    'finish_date' => $paymentTerm['finish_date'],
                    'cost' => $paymentTerm['cost'],
                ]);
            } else {
                $courseBd->paymentTerms()->create([
                    'uid' => generateUuid(),
                    'course_uid' => $courseBd->uid,
                    'name' => $paymentTerm['name'],
                    'start_date' => $paymentTerm['start_date'],
                    'finish_date' => $paymentTerm['finish_date'],
                    'cost' => $paymentTerm['cost'],
                ]);
            }
        }

        $uidsToDelete = array_diff($existingUids, $receivedUids);
        if (!empty($uidsToDelete)) {
            CoursesPaymentTermsModel::whereIn('uid', $uidsToDelete)->delete();
        }
    }

    private function syncItemsCourseEmails($items, $courseUid)
    {
        $currentEmails = CoursesEmailsContactsModel::where('course_uid', $courseUid)->pluck('email')->toArray();

        // Identificar qué items son nuevos y cuáles deben ser eliminados
        $itemsToAdd = array_diff($items, $currentEmails);
        $itemsToDelete = array_diff($currentEmails, $items);

        // Eliminar los items que ya no son necesarios
        CoursesEmailsContactsModel::where('course_uid', $courseUid)->whereIn('email', $itemsToDelete)->delete();

        // Preparar el array para la inserción masiva de nuevos items
        $insertData = [];
        foreach ($itemsToAdd as $item) {
            $insertData[] = [
                'uid' => generateUuid(),
                'course_uid' => $courseUid,
                'email' => $item
            ];
        }

        // Insertar todos los nuevos items en una única operación de BD
        CoursesEmailsContactsModel::insert($insertData);
    }

    public function getCourseStudents(Request $request, $courseUid)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $course = CoursesModel::where('uid', $courseUid)->first();

        $query = $course->students()->with(
            [
                'courseStudentDocuments' => function ($query) use ($courseUid) {
                    $query->whereHas('courseDocument', function ($query) use ($courseUid) {
                        $query->where('course_uid', $courseUid);
                    });
                },
                'courseStudentDocuments.courseDocument'
            ]
        );

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw("concat(first_name, ' ', last_name) ILIKE ?", ["%$search%"])
                    ->orWhere('nif', 'ILIKE', "%$search%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        // Aplicar paginación
        $students = $query->paginate($size);

        return response()->json($students, 200);
    }

    public function approveInscriptionsCourse(Request $request)
    {

        $selectedCourseStudents = $request->input('uids');

        DB::transaction(function () use ($selectedCourseStudents) {
            $coursesStudents = CoursesStudentsModel::whereIn('uid', $selectedCourseStudents)->with("course")->get();

            foreach ($coursesStudents as $courseStudent) {
                $courseStudent->acceptance_status = 'ACCEPTED';
                $courseStudent->save();

                dispatch(new SendUpdateEnrollmentUserCourseNotification($courseStudent));
            }

            LogsController::createLog('Aprobación de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Inscripciones aprobadas correctamente'], 200);
    }

    public function rejectInscriptionsCourse(Request $request)
    {
        $selectedCourseStudents = $request->input('uids');

        DB::transaction(function () use ($selectedCourseStudents) {
            $coursesStudents = CoursesStudentsModel::whereIn('uid', $selectedCourseStudents)->get();

            foreach ($coursesStudents as $courseStudent) {
                $courseStudent->acceptance_status = 'REJECTED';
                $courseStudent->save();

                dispatch(new SendUpdateEnrollmentUserCourseNotification($courseStudent));
            }

            LogsController::createLog('Rechazo de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Inscripciones rechazadas correctamente'], 200);
    }

    public function deleteInscriptionsCourse(Request $request)
    {
        $selectedCourseStudents = $request->input('uids');

        CoursesStudentsModel::destroy($selectedCourseStudents);

        return response()->json(['message' => 'Inscripciones eliminadas correctamente'], 200);
    }

    public function duplicateCourse(Request $request)
    {
        $courseUid = $request->input('course_uid');
        $courseBd = $this->getCourseInfo($courseUid);

        // Comprobamos que el curso no pertenezca a un programa formativo
        if ($courseBd->belongs_to_educational_program) {
            throw new OperationFailedException('No puedes duplicar un curso que pertenezca a un programa formativo', 422);
        }

        $newCourse = $this->getQueryCopyBaseCourse($courseBd);
        $newCourse->course_origin_uid = null;
        $introductionStatus = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $newCourse->course_status_uid = $introductionStatus->uid;
        $newCourse->title = $newCourse->title . " (copia)";

        DB::transaction(function () use ($newCourse, $courseBd) {
            $newCourse->save();
            $this->copyAuxiliarTablesCourse($courseBd, $newCourse);
        });

        return response()->json(['message' => 'Curso duplicado correctamente', 'course_uid' => $newCourse->uid], 200);
    }

    public function editionCourse(Request $request)
    {
        $courseUid = $request->input('course_uid');
        $courseBd = $this->getCourseInfo($courseUid);

        $this->validateNewEdition($courseBd);

        $newCourse = $this->getQueryCopyBaseCourse($courseBd);

        $introductionStatus = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $newCourse->course_status_uid = $introductionStatus->uid;

        $newCourse->title = "{$courseBd->title} (nueva edición)";
        $newCourse->course_origin_uid = $courseUid;

        DB::transaction(function () use ($newCourse, $courseBd) {
            $newCourse->save();
            $this->copyAuxiliarTablesCourse($courseBd, $newCourse);
        });

        return response()->json(['message' => 'Edición creada correctamente', 'course_uid' => $newCourse->uid], 200);
    }

    private function duplicateStructure($courseBd, $newCourse)
    {
        foreach ($courseBd->blocks as $block) {
            $newBlock = $block->replicate();
            $newBlock->course_uid = $newCourse->uid;
            $newBlock->uid = generateUuid();
            $newBlock->push();

            // Asignamos las competencias al bloque
            $competencesToSync = [];
            foreach ($block->competences as $competence) {
                $competencesToSync[$competence->uid] = [
                    'uid' => generateUuid(),
                    'course_block_uid' => $newBlock->uid,
                    'competence_uid' => $competence->uid
                ];
            }

            $newBlock->competences()->sync($competencesToSync);

            $learningResultsToSync = [];
            foreach ($block->learningResults as $learningResult) {
                $learningResultsToSync[$learningResult->uid] = [
                    'uid' => generateUuid(),
                    'course_block_uid' => $newBlock->uid,
                    'learning_result_uid' => $learningResult->uid
                ];
            }
            $newBlock->learningResults()->sync($learningResultsToSync);

            foreach ($block->subBlocks as $subBlock) {
                $newSubBlock = $subBlock->replicate();
                $newSubBlock->uid = generateUuid();
                $newSubBlock->block_uid = $newBlock->uid;
                $newSubBlock->push();

                foreach ($subBlock->elements as $element) {
                    $newElement = $element->replicate();
                    $newElement->uid = generateUuid();
                    $newElement->subblock_uid = $newSubBlock->uid;
                    $newElement->push();

                    foreach ($element->subElements as $subElement) {
                        $newSubElement = $subElement->replicate();
                        $newSubElement->uid = generateUuid();
                        $newSubElement->element_uid = $newElement->uid;
                        $newSubElement->push();
                    }
                }
            }
        }
    }

    private function duplicateCourseTeachers($courseBd, $newCourse)
    {
        $teachers = $courseBd->teachers->pluck('uid')->toArray();
        $teachersToSync = [];

        foreach ($teachers as $teacherUid) {
            $teachersToSync[$teacherUid] = [
                'uid' => generateUuid(),
                'course_uid' => $newCourse->uid,
                'user_uid' => $teacherUid
            ];
        }
        $newCourse->teachers()->sync($teachersToSync);
    }

    private function duplicateCourseTags($courseBd, $newCourse)
    {
        $tags = $courseBd->tags->pluck('tag')->toArray();
        $tagsToAdd = [];
        foreach ($tags as $tag) {
            $tagsToAdd[] = [
                'uid' => generateUuid(),
                'course_uid' => $newCourse->uid,
                'tag' => $tag,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        CoursesTagsModel::insert($tagsToAdd);
    }

    private function duplicateCourseCategories($courseBd, $newCourse)
    {
        $categories = $courseBd->categories->pluck('uid')->toArray();
        $categoriesToSync = [];
        foreach ($categories as $categoryUid) {
            $categoriesToSync[] = [
                'uid' => generateUuid(),
                'course_uid' => $newCourse->uid,
                'category_uid' => $categoryUid
            ];
        }
        $newCourse->categories()->sync($categoriesToSync);
    }

    private function getCourseInfo($courseUid)
    {
        return CoursesModel::where('uid', $courseUid)->with([
            'teachers',
            'tags',
            'categories',
            'blocks',
            'courseDocuments',
            'paymentTerms',
            'blocks.competences',
            'blocks.subBlocks',
            'blocks.subBlocks.elements',
            'blocks.subBlocks.elements.subElements',
        ])->first();
    }

    private function getQueryCopyBaseCourse($courseOrigin)
    {
        $newCourse = $courseOrigin->replicate();
        $newCourse->uid = generateUuid();
        $newCourse->identifier = $this->generateCourseIdentifier();
        $newCourse->creator_user_uid = Auth::user()['uid'];

        return $newCourse;
    }

    private function copyAuxiliarTablesCourse($courseBd, $newCourse)
    {
        $this->duplicateCourseTeachers($courseBd, $newCourse);
        $this->duplicateCourseTags($courseBd, $newCourse);
        $this->duplicateCourseCategories($courseBd, $newCourse);
        $this->duplicateStructure($courseBd, $newCourse);
        $this->duplicateCourseDocuments($courseBd, $newCourse);

        if ($courseBd->payment_mode == "INSTALLMENT_PAYMENT") {
            $this->duplicateCoursePaymentTerms($courseBd, $newCourse);
        }
    }

    private function duplicateCoursePaymentTerms($courseBd, $newCourse)
    {

        $paymentTerms = $courseBd->paymentTerms;

        foreach ($paymentTerms as $paymentTerm) {
            $newPaymentTerm = $paymentTerm->replicate();
            $newPaymentTerm->uid = generateUuid();
            $newPaymentTerm->course_uid = $newCourse->uid;
            $newPaymentTerm->save();
        }
    }

    private function duplicateCourseDocuments($courseBd, $newCourse)
    {
        $documents = $courseBd->courseDocuments;

        foreach ($documents as $document) {
            $newDocument = $document->replicate();
            $newDocument->uid = generateUuid();
            $newDocument->course_uid = $newCourse->uid;
            $newDocument->save();
        }
    }

    // Se comprueba que no hubiera creada una edición de ese curso
    private function validateNewEdition($courseBd)
    {
        // Comprobar que no sea un curso de programa
        if ($courseBd->belongs_to_educational_program) {
            throw new OperationFailedException('No se puede crear una edición de un curso de programa');
        }

        $existingEdition = CoursesModel::where('course_origin_uid', $courseBd->uid)
            ->whereHas('status', function ($query) {
                $query->where('code', '!=', 'RETIRED');
            })
            ->exists();
        if ($existingEdition) {
            throw new OperationFailedException('Ya existe una edición activa de este curso');
        }
    }

    private function syncStructure($structure, $courseUid)
    {
        $this->syncDeletedCompositionCourseStructure($courseUid, $structure);

        foreach ($structure as $blockData) {
            $this->syncBlock($blockData, $courseUid);
        }
    }

    private function syncBlock($blockData, $courseUid)
    {
        $blockUid = $blockData['uid'] ?: generateUuid();
        BlocksModel::updateOrCreate(
            ['uid' => $blockUid],
            [
                'course_uid' => $courseUid,
                'type' => $blockData['type'],
                'name' => $blockData['name'],
                'description' => $blockData['description'],
                'order' => $blockData['order']
            ]
        );

        $blockModel = BlocksModel::where('uid', $blockUid)->first();
        $this->syncLearningResults($blockModel, $blockData['learningResults']);

        if (isset($blockData['subBlocks'])) {
            foreach ($blockData['subBlocks'] as $subBlockData) {
                $this->syncSubBlock($subBlockData, $blockModel['uid']);
            }
        }
    }

    private function syncLearningResults($blockModel, $learningResults)
    {
        $learningResultsToSync = [];
        foreach ($learningResults as $learningResultUid) {
            $learningResultsToSync[$learningResultUid] = [
                'uid' => generateUuid(),
                'course_block_uid' => $blockModel->uid,
                'learning_result_uid' => $learningResultUid
            ];
        }
        $blockModel->learningResults()->sync($learningResultsToSync);
    }

    private function syncSubBlock($subBlockData, $blockUid)
    {
        $subBlock = SubBlocksModel::updateOrCreate(
            ['uid' => $subBlockData['uid'] ?: generateUuid()],
            [
                'block_uid' => $blockUid,
                'name' => $subBlockData['name'],
                'description' => $subBlockData['description'],
                'order' => $subBlockData['order']
            ]
        );

        if (isset($subBlockData['elements'])) {
            foreach ($subBlockData['elements'] as $elementData) {
                $this->syncElement($elementData, $subBlock->uid);
            }
        }
    }

    private function syncElement($elementData, $subBlockUid)
    {
        $element = ElementsModel::updateOrCreate(
            ['uid' => $elementData['uid'] ?: generateUuid()],
            [
                'subblock_uid' => $subBlockUid,
                'name' => $elementData['name'],
                'description' => $elementData['description'],
                'order' => $elementData['order']
            ]
        );

        if (isset($elementData['subElements'])) {
            foreach ($elementData['subElements'] as $subElementData) {
                $this->syncSubElement($subElementData, $element->uid);
            }
        }
    }

    private function syncSubElement($subElementData, $elementUid)
    {
        SubElementsModel::updateOrCreate(
            ['uid' => $subElementData['uid'] ?: generateUuid()],
            [
                'element_uid' => $elementUid,
                'name' => $subElementData['name'],
                'description' => $subElementData['description'],
                'order' => $subElementData['order']
            ]
        );
    }

    private function syncDeletedCompositionCourseStructure($courseUid, $structure)
    {
        // Eliminar cualquier bloque, subbloque, elemento o subelemento que no esté en la lista de uid
        $blocksUids = collect($structure)->pluck('uid');
        BlocksModel::where('course_uid', $courseUid)->whereNotIn('uid', $blocksUids)->delete();

        $subblocksUids = collect($structure)->pluck('subBlocks.*.uid')->flatten();

        if (!$subblocksUids->contains(null)) {
            SubblocksModel::whereIn('block_uid', $blocksUids)->whereNotIn('uid', $subblocksUids)->delete();
        } else {
            SubblocksModel::whereIn('block_uid', $blocksUids)->delete();
        }

        $elementsUids = collect($structure)->pluck('subBlocks.*.elements.*.uid')->flatten();

        if ($elementsUids->contains(null)) {
            ElementsModel::whereIn('subblock_uid', $subblocksUids)->whereNotIn('uid', $elementsUids)->delete();
        } else {
            ElementsModel::whereIn('subblock_uid', $subblocksUids)->delete();
        }

        $subelementsUids = collect($structure)->pluck('subBlocks.*.elements.*.subElements.*.uid')->flatten();

        if ($subelementsUids->contains(null)) {
            SubelementsModel::whereIn('element_uid', $elementsUids)->whereNotIn('uid', $subelementsUids)->delete();
        } else {
            SubelementsModel::whereIn('element_uid', $elementsUids)->delete();
        }
    }

    public function getAllCompetences()
    {
        return CompetenceFrameworksModel::with([
            'levels',
            'allSubcompetences',
            'allSubcompetences.learningResults',
            'allSubcompetences.allSubcompetences',
            'allSubcompetences.allSubcompetences.learningResults'
        ])->get();
    }
    public function enrollStudents(Request $request)
    {

        $users = $request->get('usersToEnroll');

        if (!$users || !count($users)) {
            throw new OperationFailedException('No se han seleccionado alumnos');
        }

        $usersenrolled = false;

        foreach ($users as $user) {

            $existingEnrollment = CoursesStudentsModel::where('course_uid', $request->get('courseUid'))
                ->where('user_uid', $user)
                ->first();

            if ($existingEnrollment) {
                $usersenrolled = true;
                continue;
            }

            $enroll = new CoursesStudentsModel();
            $enroll->uid = generateUuid();
            $enroll->course_uid = $request->get('courseUid');
            $enroll->user_uid = $user;
            $enroll->acceptance_status = 'ACCEPTED';
            $messageLog = "Alumno añadido a curso";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Cursos', auth()->user()->uid);
            });
        }

        $message = "Alumnos añadidos al curso";

        if ($usersenrolled) {
            $message = "Alumnos añadidos al curso. Los ya registrados no se han añadido.";
        }

        return response()->json(['message' => $message], 200);
    }

    public function downloadDocumentStudent(Request $request)
    {
        $uidDocument = $request->get('uidDocument');
        $document = CoursesStudentDocumentsModel::where('uid', $uidDocument)->first();

        return response()->download(storage_path($document->document_path));
    }

    public function enrollStudentsCsv(Request $request)
    {

        $file = $request->file('attachment');
        $courseUid = $request->get('course_uid');

        $reader = Reader::createFromPath($file->path());

        foreach ($reader as $key => $row) {

            if ($key > 0) {

                $existingUser = UsersModel::where('email', $row[3])
                    ->first();

                if ($existingUser) {
                    $this->enrollUserCsv($row, $existingUser->uid, $courseUid);
                } else {
                    $this->validateUserCsv($row, $key);
                    $this->singUpUser($row, $courseUid);
                }
            }
        }

        $message = "Alumnos añadidos al curso. Los ya registrados no se han añadido.";

        return response()->json(['message' => $message], 200);
    }

    private function validateUserCsv($user, $index)
    {
        $validatorNif = Validator::make(
            ['nif' => $user[2]],
            ['nif' => [new NifNie]],
            ['nif' => 'required|nif|unique:users,nif'],
        );

        if ($validatorNif->fails()) {
            throw new OperationFailedException("El NIF/NIE de la línea " . $index . " no es válido");
        }

        $validatorEmailValid = Validator::make(
            ['correo' => $user[3]],
            ['correo' => 'email'],
        );

        if ($validatorEmailValid->fails()) {
            throw new OperationFailedException("El correo de la línea " . $index . " no es válido");
        }
    }

    public function singUpUser($row, $courseUid)
    {

        $newUserUid = generateUuid();

        $newUser = new UsersModel();
        $newUser->uid = $newUserUid;
        $newUser->first_name = $row[0];
        $newUser->last_name = $row[1];
        $newUser->nif = $row[2];
        $newUser->email = $row[3];


        $messageLog = "Alumno dado de alta";

        DB::transaction(function () use ($newUser, $messageLog) {
            $newUser->save();
            LogsController::createLog($messageLog, 'Cursos', auth()->user()->uid);
        });

        $this->enrollUserCsv($row, $newUserUid, $courseUid);
    }
    public function enrollUserCsv($row, $userUid, $courseUid)
    {

        $existingEnrollment = CoursesStudentsModel::where('course_uid', $courseUid)
            ->where('user_uid', $userUid)
            ->first();

        if (!$existingEnrollment) {

            $enroll = new CoursesStudentsModel();
            $enroll->uid = generateUuid();
            $enroll->course_uid = $courseUid;
            $enroll->user_uid = $userUid;
            $enroll->acceptance_status = 'ACCEPTED';
            $messageLog = "Alumno añadido a curso";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Cursos', auth()->user()->uid);
            });
        }
    }

    private function generateCourseIdentifier()
    {
        $coursesCount = CoursesModel::count();
        return 'CUR-' . str_pad($coursesCount + 1, 4, '0', STR_PAD_LEFT);
    }
}
