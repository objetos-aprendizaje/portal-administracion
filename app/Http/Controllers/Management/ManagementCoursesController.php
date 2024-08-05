<?php

namespace App\Http\Controllers\Management;

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
use App\Models\EducationalProgramTypesModel;
use App\Models\CourseTypesModel;
use App\Models\UsersModel;
use App\Models\CategoriesModel;
use App\Models\CentersModel;
use App\Models\CompetencesModel;
use App\Models\CourseCategoriesModel;
use App\Models\CoursesEmailsContactsModel;
use App\Models\CoursesPaymentTermsModel;
use App\Models\CoursesStudentDocumentsModel;
use App\Models\CoursesStudentsModel;
use App\Models\CoursesTagsModel;

use Illuminate\Support\Facades\DB;

use App\Models\CourseStatusesModel;
use App\Models\CoursesUsersModel;
use App\Models\EducationalProgramsModel;
use App\Models\ElementsModel;
use App\Models\GeneralOptionsModel;
use App\Models\LmsSystemsModel;
use App\Models\SubblocksModel;
use App\Models\SubelementsModel;
use App\Models\UserRolesModel;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use League\Csv\Reader;

use App\Rules\NifNie;
use App\Services\KafkaService;

class ManagementCoursesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $courses = CoursesModel::with('status')->get()->toArray();
        $calls = CallsModel::get()->toArray();
        $courses_statuses = CourseStatusesModel::all()->toArray();
        $educationals_programs_types = EducationalProgramTypesModel::all()->toArray();
        $courses_types = CourseTypesModel::all()->toArray();
        $centers = CentersModel::all()->toArray();

        $teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->with('roles')->get()->toArray();

        $students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })->with('roles')->get()->toArray();

        $categories = CategoriesModel::with('parentCategory')->get()->toArray();

        $educational_programs = EducationalProgramsModel::all()->toArray();

        $competences = CompetencesModel::whereNull('parent_competence_uid')->with('subcompetences')->orderBy('name', 'ASC')->get()->toArray();

        $lmsSystems = LmsSystemsModel::all();

        if (!empty($categories)) $categories = $this->buildNestedCategories($categories);

        $rolesUser = Auth::user()['roles']->pluck("code")->toArray();
        $variables_js = [
            "operationByCalls" => GeneralOptionsModel::where(['option_name' => 'operation_by_calls'])->first()->option_value == 1,
            "competences" => $competences,
            "frontUrl" => env('FRONT_URL'),
            "rolesUser" => $rolesUser
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
                "courses_statuses" => $courses_statuses,
                "educationals_programs_types" => $educationals_programs_types,
                "courses_types" => $courses_types,
                "teachers" => $teachers,
                "categories" => $categories,
                "students" => $students,
                "lmsSystems" => $lmsSystems,
                "tabulator" => true,
                "tomselect" => true,
                "flatpickr" => true,
                "educational_programs" => $educational_programs,
                "variables_js" => $variables_js,
                "treeselect" => true,
                "centers" => $centers,
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
    function buildNestedCategories($categories, $parent = null, $prefix = '', $indicator = '')
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
        $courses_bd = CoursesModel::whereIn('uid', array_column($changesCoursesStatuses, "uid"))->with('status')->get()->keyBy('uid');

        // Excluímos los estados a los que no se pueden cambiar manualmente.
        $statuses_courses = CourseStatusesModel::whereNotIn('code', ['DEVELOPMENT', 'PENDING_INSCRIPTION', 'FINISHED'])->get()->keyBy('code');
        // Aquí iremos almacenando los datos de los cursos que se van a actualizar

        DB::transaction(function () use ($changesCoursesStatuses, $courses_bd, $statuses_courses) {
            // Recorremos los cursos que nos vienen en el request y los comparamos con los de la base de datos
            foreach ($changesCoursesStatuses as $changeCourseStatus) {
                // Obtenemos el curso de la base de datos
                $course = $courses_bd[$changeCourseStatus['uid']];
                $status = $statuses_courses[$changeCourseStatus['status']];
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
                    $this->sendNotificationCourseAcceptedPublicationToKafka($course);
                }

                dispatch(new SendChangeStatusCourseNotification($course->toArray()));
            }

            LogsController::createLog('Cambio de estado de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Se han actualizado los estados de los cursos correctamente'], 200);
    }

    private function updateStatusCourse($course, $status, $reason)
    {
        $course->course_status_uid = $status->uid;
        $course->status_reason = $reason;
        $course->save();
    }

    private function sendNotificationCourseAcceptedPublicationToKafka($course)
    {
        $courseData = [
            'course_uid' => $course->uid,
            'title' => $course->title,
            "description" => $course->description,
            'realization_start_date' => $course->realization_start_date,
            'realization_finish_date' => $course->realization_start_date,
        ];

        $kafkaService = new KafkaService();
        $kafkaService->sendMessage('course_accepted_publication', $courseData, 'course_accepted_publication');
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
        } else if (in_array("TEACHER", $roles)) {
            $query = $this->buildQueryForTeacher();
        }

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%")
                ->orWhere('courses.uid', $search)
                ->orWhere('courses.identifier', $search);
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        if ($filters) $this->applyFilters($filters, $query);

        //dd($query->get()->toArray());

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    private function buildQueryCoursesBase()
    {
        return CoursesModel::query()
            ->leftJoin('course_statuses as status', 'courses.course_status_uid', '=', 'status.uid')
            ->leftJoin('calls as calls', 'courses.call_uid', '=', 'calls.uid')
            ->leftJoin('educational_programs as educational_programs', 'courses.educational_program_uid', '=', 'educational_programs.uid')
            ->leftJoin('educational_program_types as educational_program_types', 'courses.educational_program_type_uid', '=', 'educational_program_types.uid')
            ->leftJoin('course_types as course_types', 'courses.course_type_uid', '=', 'course_types.uid')
            ->leftJoin('centers as centers', 'courses.center_uid', '=', 'centers.uid')
            ->with('tags')
            ->with('contact_emails')
            ->with('teachers_coordinate')
            ->with('teachers_no_coordinate')
            ->with('categories')
            ->select(
                'courses.*',
                'status.name as status_name',
                'status.code as status_code',
                'calls.name as calls_name',
                'educational_programs.name as educational_programs_name',
                'educational_program_types.name as educational_program_types_name',
                'course_types.name as course_types_name',
                'centers.name as centers_name',
            );
    }

    private function buildQueryForTeacher()
    {
        $userUid = Auth::user()['uid'];

        $queryCoursesBase = $this->buildQueryCoursesBase();

        $queryCoursesForTeachers = $queryCoursesBase->where(function ($query) use ($userUid) {
            $query->where('courses.creator_user_uid', '=', $userUid)
                ->orWhereHas('teachers_coordinate', function ($query) use ($userUid) {
                    $query->where('user_uid', '=', $userUid);
                })
                ->orWhereHas('teachers_no_coordinate', function ($query) use ($userUid) {
                    $query->where('user_uid', '=', $userUid);
                });
        });

        return $queryCoursesForTeachers;
    }

    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == "center") {
                $query->where("center", 'LIKE', "%{$filter['value']}%");
            } elseif ($filter['database_field'] == 'inscription_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('inscription_start_date', '<=', $filter['value'][1])
                        ->where('inscription_finish_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('inscription_start_date', '<=', $filter['value'])
                        ->whereDate('inscription_finish_date', '>=', $filter['value']);
                }
            } elseif ($filter['database_field'] == 'realization_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('realization_start_date', '<=', $filter['value'][1])
                        ->where('realization_finish_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('realization_start_date', '<=', $filter['value'])
                        ->whereDate('realization_finish_date', '>=', $filter['value']);
                }
            } elseif ($filter['database_field'] == "coordinators_teachers") {
                $teachers_uids = $filter['value'];
                $query->whereHas('teachers', function ($query) use ($teachers_uids) {
                    $query->whereIn('users.uid', $teachers_uids)
                        ->where('type', 'COORDINATOR');
                });
            } elseif ($filter['database_field'] == "no_coordinators_teachers") {
                $teachers_uids = $filter['value'];
                $query->whereHas('teachers', function ($query) use ($teachers_uids) {
                    $query->whereIn('users.uid', $teachers_uids)
                        ->where('type', 'NO_COORDINATOR');
                });
            } elseif ($filter['database_field'] == 'creator_user_uid') {

                $query->whereIn('creator_user_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'categories') {

                $categories_uids = $filter['value'];
                $query->whereHas('categories', function ($query) use ($categories_uids) {
                    $query->whereIn('categories.uid', $categories_uids);
                });
            } else if ($filter['database_field'] == 'course_statuses') {
                $query->whereIn('course_status_uid', $filter['value']);
            } else if ($filter['database_field'] == 'calls') {
                $query->whereIn('call_uid', $filter['value']);
            } else if ($filter['database_field'] == 'educational_programs') {
                $query->whereIn('educational_program_type_uid', $filter['value']);
            } else if ($filter['database_field'] == 'course_types') {
                $query->whereIn('course_type_uid', $filter['value']);
            } else if ($filter['database_field'] == 'min_ects_workload') {
                $query->where('ects_workload', '>=', $filter['value']);
            } else if ($filter['database_field'] == 'max_ects_workload') {
                $query->where('ects_workload', '<=', $filter['value']);
            } else if ($filter['database_field'] == 'min_cost') {
                $query->where('cost', '>=', $filter['value']);
            } else if ($filter['database_field'] == 'max_cost') {
                $query->where('cost', '<=', $filter['value']);
            } else if ($filter['database_field'] == 'min_required_students') {
                $query->where('min_required_students', '>=', $filter['value']);
            } else if ($filter['database_field'] == 'max_required_students') {
                $query->where('min_required_students', '<=', $filter['value']);
            } else if ($filter['database_field'] == 'competences') {
                $query->with([
                    'blocks' => function ($query) {
                        $query->orderBy('order', 'asc');
                    },
                    'blocks.competences'
                ])->whereHas('blocks.competences', function ($query) use ($filter) {
                    $query->whereIn('competences.uid', $filter['value']);
                });
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }

    /**
     * Obtiene un curso por uid
     */

    public function getCourse($course_uid)
    {

        if (!$course_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $course = CoursesModel::where('uid', $course_uid)->with([
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
        $course_uid = $request->input('course_uid');

        if ($course_uid) {
            $isNew = false;
            $course_bd = CoursesModel::where('uid', $course_uid)->with("educational_program")->first();
            $this->checkStatusCourse($course_bd);

            // Si el curso está ya añadido a un programa educativo, validamos las fechas de realización
            if ($course_bd->status->code == "ADDED_EDUCATIONAL_PROGRAM") {
                $this->checkRealizationDatesCourseAddEducationalProgram($request, $course_bd);
            }
        } else {
            $isNew = true;
            $course_bd = new CoursesModel();
            $course_bd->uid = generate_uuid();

            // Número de cursos existentes
            $course_bd->identifier = $this->generateCourseIdentifier();
            $course_bd->creator_user_uid = Auth::user()['uid'];
        }

        if ($course_bd->course_origin_uid) {
            $errors = $this->validateCourseEdition($request);
        } else {
            $errors = $this->validateCourseFields($request);
        }

        if (!$errors->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errors], 422);
        }

        $action = $request->input('action');
        $belongsEducationalProgram = $request->input('belongs_to_educational_program');

        // En función de si el curso pertenece o no a un programa formativo, le corresponderá un estado u otro
        if ($belongsEducationalProgram) {
            $newCourseStatus = $this->statusCourseBelongsEducationalProgram($action, $course_bd);
        } else {
            $newCourseStatus = $this->statusCourseNotBelongsEducationalProgram($action, $course_bd);
        }

        DB::transaction(function () use ($request, $course_bd, $belongsEducationalProgram, $isNew, $newCourseStatus) {
            $isManagement = Auth::user()->hasAnyRole(['MANAGEMENT']);

            if ($newCourseStatus) {
                $course_bd->course_status_uid = $newCourseStatus->uid;
            }

            // En función de si el curso pertenece a una nueva edición o no y no es gestor, se actualizarán o no ciertos campos
            if ($course_bd->course_origin_uid && !$isManagement) {
                $this->updateCourseFieldsNewEdition($request, $course_bd);
                $course_bd->save();
                // Documentos
                $validateStudentRegistrations = $request->input('validate_student_registrations');
                if ($validateStudentRegistrations) $this->updateDocumentsCourse($request, $course_bd);
                else $course_bd->courseDocuments()->delete();
            } else {
                $this->updateCourseFields($request, $course_bd, $belongsEducationalProgram);
                $course_bd->save();
                // Campos de tablas auxiliares
                $this->updateAuxiliarDataCourse($course_bd, $request);
            }

            $image_file = $request->file('image_input_file');
            if ($image_file) $this->updateImageField($image_file, $course_bd);

            if ($newCourseStatus && $newCourseStatus->code === "PENDING_APPROVAL") {
                dispatch(new SendCourseNotificationToManagements($course_bd->toArray()));
            }

            $course_bd->save();

            LogsController::createLog(($isNew) ? 'Curso añadido' : 'Curso actualizado', 'Cursos', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => ($isNew) ? 'Se ha añadido el curso correctamente' : 'Se ha actualizado el curso correctamente'], 200);
    }

    private function checkRealizationDatesCourseAddEducationalProgram($request, $course_bd)
    {
        $realization_start_date = $request->input('realization_start_date');
        $realization_finish_date = $request->input('realization_finish_date');

        $educational_program_start_date = $course_bd->educational_program->realization_start_date;
        $educational_program_finish_date = $course_bd->educational_program->realization_finish_date;

        if ($realization_start_date < $educational_program_start_date || $realization_finish_date > $educational_program_finish_date) {
            throw new OperationFailedException('Las fechas de realización deben estar dentro del rango del programa educativo', 422);
        }
    }

    private function checkStatusCourse($course_bd)
    {
        $isUserManagement = Auth::user()->hasAnyRole(['MANAGEMENT']);

        // Si es gestor, siempre podrá editar el curso
        if ($isUserManagement) return;

        $statusesAllowEdit = ["INTRODUCTION", "UNDER_CORRECTION_APPROVAL", "UNDER_CORRECTION_PUBLICATION"];
        if (!in_array($course_bd->status->code, $statusesAllowEdit) && !$course_bd->belongs_to_educational_program) {
            throw new OperationFailedException('No puedes editar un curso que no esté en estado de introducción o subsanación', 422);
        } else if ($course_bd->status->code == "ADDED_EDUCATIONAL_PROGRAM" && $course_bd->belongs_to_educational_program) {
            if (!in_array($course_bd->educational_program->status->code, $statusesAllowEdit)) {
                throw new OperationFailedException('No puedes editar un curso cuyo programa formativo no esté en estado de introducción o subsanación', 422);
            }
        }
    }

    private function statusCourseEdition($action, $course_bd)
    {
        $statuses = CourseStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'ACCEPTED_PUBLICATION',
            'PENDING_APPROVAL'
        ])->get()->keyBy('code');

        $actualStatusCourse = $course_bd->status->code ?? null;

        // Comprobamos si es necesario aprobar o no los cursos en función de la configuración
        // del gestor
        if ($action === "draft" && !$actualStatusCourse) return $statuses['INTRODUCTION'];
        else if ($action === "submit") {
            $necessaryApprovalEditions = app('general_options')['necessary_approval_editions'];
            return $necessaryApprovalEditions ? $statuses['PENDING_APPROVAL'] : $statuses['ACCEPTED_PUBLICATION'];
        } else return null;
    }

    private function updateAuxiliarDataCourse($course_bd, $request)
    {

        $belongsEducationalProgram = $request->input('belongs_to_educational_program');
        if (!$belongsEducationalProgram) {
            // Tags
            $this->updateTagsCourse($request, $course_bd);

            // Categorías
            $this->updateCategoriesCourse($request, $course_bd);

            // Documentos
            $validateStudentRegistrations = $request->input('validate_student_registrations');
            if ($validateStudentRegistrations) {
                $this->updateDocumentsCourse($request, $course_bd);
            } else $course_bd->courseDocuments()->delete();

            // Plazos de pago
            $paymentMode = $request->input('payment_mode');
            if ($paymentMode == "INSTALLMENT_PAYMENT") {
                $this->updatePaymentTerms($request, $course_bd);
            } else if ($paymentMode == "SINGLE_PAYMENT") {
                $course_bd->paymentTerms()->delete();
            }
        } else {
            $course_bd->categories()->detach();
            $course_bd->tags()->delete();
            $course_bd->courseDocuments()->delete();
            $course_bd->paymentTerms()->delete();
        }

        // Guardado de profesores
        $this->updateTeachers($request, $course_bd);

        // Estructura
        $structure = $request->input('structure');
        $structure = json_decode($structure, true);
        $this->syncStructure($structure, $course_bd->uid);

        // Emails de contacto
        $contact_emails = json_decode($request->input('contact_emails'), true);
        $this->syncItemsCourseEmails($contact_emails, $course_bd->uid);
    }

    private function updatePaymentTerms($request, $course_bd)
    {
        $paymentTerms = $request->input('payment_terms');
        $paymentTerms = json_decode($paymentTerms, true);
        $this->syncPaymentTerms($paymentTerms, $course_bd);
    }

    private function updateTagsCourse($request, $course_bd)
    {
        $tags = $request->input('tags');
        $tags = json_decode($request->input('tags'), true);
        $this->syncItemsTags($tags, $course_bd->uid);
    }

    private function updateCategoriesCourse($request, $course_bd)
    {
        $categories = $request->input('categories');
        $categories = json_decode($categories, true);
        $this->syncCategories($categories, $course_bd);
    }

    private function updateDocumentsCourse($request, $course_bd)
    {
        $documents = $request->input('documents');
        $documents = json_decode($documents, true);
        $course_bd->updateDocuments($documents);
    }

    private function validateCourseFields(Request $request)
    {
        $validatorMessages = $this->getValidatorCourseMessages();

        $rules = [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'contact_information' => 'nullable|string',
            'course_type_uid' => 'required|string',
            'educational_program_type_uid' => 'required|string',
            'min_required_students' => 'nullable|integer',
            'realization_start_date' => 'required',
            'realization_finish_date' => 'required',
            'presentation_video_url' => 'nullable|url',
            'objectives' => 'nullable|string',
            'ects_workload' => 'required|numeric',
            'validate_student_registrations' => 'required|boolean',
            'lms_url' => 'nullable|url',
            'lms_system_uid' => 'required_with:lms_url',
            'cost' => 'nullable|numeric',
            'featured_big_carrousel_title' => 'required_if:featured_big_carrousel,1',
            'featured_big_carrousel_description' => 'required_if:featured_big_carrousel,1',
            'center_uid' => 'required|string',
            'evaluation_criteria' => 'required_if:validate_student_registrations,1',
            'calification_type' => 'required|string',
            'teacher_no_coordinators' => [
                function ($attribute, $value, $fail) use ($request) {
                    $teacher_coordinators = json_decode($request->input('teacher_coordinators'), true);
                    $value = json_decode($value, true);
                    $duplicates = array_intersect($value, $teacher_coordinators);
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

        if ($belongsToEducationalProgram) {
            $this->addRulesIfBelongsToEducationalProgram($rules);
        } else {
            $this->addRulesIfNotBelongsEducationalProgram($request, $rules);
        }

        $validator = Validator::make($request->all(), $rules, $validatorMessages);

        return $validator->errors();
    }

    private function validateCourseEdition($request)
    {

        $rules = [
            'inscription_start_date' => 'required',
            'inscription_finish_date' => 'required|after_or_equal:inscription_start_date',
            'min_required_students' => 'nullable|integer|min:0',
            'featured_big_carrousel_title' => 'required_if:featured_big_carrousel,1',
            'featured_big_carrousel_description' => 'required_if:featured_big_carrousel,1',
            'featured_big_carrousel_image_path' => 'required_if:featured_big_carrousel,1',
            'lms_system_uid' => 'required_with:lms_url',
            'call_uid' => 'required'
        ];

        if (app('general_options')['operation_by_calls']) {
            $rules['call_uid'] = 'required';
        }

        $validateStudentRegistrations = $request->input('validate_student_registrations');
        $cost = $request->input('cost');
        if ($validateStudentRegistrations || $cost && $cost > 0) {
            $rules['enrolling_start_date'] = 'required|after_or_equal:inscription_finish_date';
            $rules['enrolling_finish_date'] = 'required|after_or_equal:enrolling_start_date';

            $rules['realization_start_date'] = 'required|after_or_equal:enrolling_finish_date';
            $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
        } else {
            $rules['realization_start_date'] = 'required|after_or_equal:inscription_finish_date';
            $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
        }

        $validatorMessages = $this->getValidatorCourseMessages();
        $validator = Validator::make($request->all(), $rules, $validatorMessages);

        return $validator->errors();
    }

    private function getValidatorCourseMessages()
    {

        $messages = [
            'title.required' => 'Introduce el título del curso.',
            'course_type_uid.required' => 'Selecciona el tipo de curso.',
            'educational_program_type_uid.required' => 'Selecciona el tipo de programa educativo.',
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
            'lms_system_uid.required_with' => 'Debes seleccionar un LMS si especificas una URL',
            'call_uid.required' => 'Selecciona la convocatoria del curso.',
            'center_uid' => 'Debes especificar un centro',
            'featured_big_carrousel_title.required_if' => 'Debes especificar un título',
            'featured_big_carrousel_description.required_if' => 'Debes especificar una descripción',
            'evaluation_criteria.required_if' => 'Debes especificar unos criterios de evaluación si activas la validación de estudiantes',
            'realization_start_date.after_or_equal' => 'La fecha de inicio de realización no puede ser anterior a la fecha de fin de inscripción.',
            'realization_finish_date.after_or_equal' => 'La fecha de finalización de realización no puede ser anterior a la fecha de inicio de realización.',
            'calification_type.required' => 'Debes especificar un tipo de calificación',
            'min_required_students.min' => 'El número mínimo de estudiantes no puede ser negativo.',
            'enrolling_start_date.required' => 'La fecha de inicio de matriculación es obligatoria.',
            'enrolling_finish_date.required' => 'La fecha de fin de matriculación es obligatoria.',
            'enrolling_start_date.after_or_equal' => 'La fecha de inicio de matriculación no puede ser anterior a la de fin de inscripción.',
            'enrolling_finish_date.after_or_equal' => 'La fecha de fin de matriculación no puede ser anterior a la de inicio de matriculación.',
            'featured_big_carrousel_image_path.required_if' => 'Debes seleccionar una imagen para el carrusel grande',
        ];

        return $messages;
    }

    private function addRulesIfNotBelongsEducationalProgram($request, &$rules)
    {

        $rules['inscription_start_date'] = 'required';
        $rules['inscription_finish_date'] = 'required|after_or_equal:inscription_start_date';

        $cost = $request->input('cost');
        $validateStudentRegistrations = $request->input('validate_student_registrations');
        $paymentMode = $request->input('payment_mode');

        if ($cost && $cost > 0 || $validateStudentRegistrations) {
            $rules['enrolling_start_date'] = 'required|after_or_equal:inscription_finish_date';
            $rules['enrolling_finish_date'] = 'required|after_or_equal:enrolling_start_date';

            $rules['realization_start_date'] = 'required|after_or_equal:enrolling_finish_date';
            $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
        }

        if ($paymentMode == "INSTALLMENT_PAYMENT") {
            $rules['payment_terms'] = [
                'required',
                function ($attribute, $value, $fail) {
                    $value = json_decode($value, true);
                    $validation = $this->validatePaymentTerms($value);
                    if ($validation !== true) $fail($validation);
                },
            ];
        }
    }

    // Validación del bloque de plazos de pago
    private function validatePaymentTerms($paymentTerms)
    {
        $fields = ['name', 'start_date', 'finish_date', 'cost'];

        if (!count($paymentTerms)) return "Debes especificar al menos un plazo de pago";

        foreach ($paymentTerms as $paymentTerm) {
            if ($paymentTerm['cost'] <= 0) return "El coste de los plazos de pago no puede ser negativo";
            else if (!$paymentTerm['name']) return "Debes especificar un nombre para el plazo de pago";

            // Comprobamos si le falta algún campo
            foreach ($fields as $field) {
                if (!array_key_exists($field, $paymentTerm)) return false;
            }
        }

        return true;
    }

    private function addRulesIfBelongsToEducationalProgram(&$rules)
    {
        $rules['realization_start_date'] = 'required';
        $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
    }

    private function addRulesIfCallsIsEnabled(&$rules)
    {
        $operation_by_calls = GeneralOptionsModel::where(['option_name' => 'operation_by_calls'])->first()->option_value;

        if ($operation_by_calls) $rules['call_uid'] = 'required|string';
        else $rules['call_uid'] = 'nullable|string';
    }

    private function statusCourseBelongsEducationalProgram($action, $course_bd)
    {
        $statuses = CourseStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'READY_ADD_EDUCATIONAL_PROGRAM'
        ])->get()->keyBy('code');

        if ($action === "submit") {
            return $statuses['READY_ADD_EDUCATIONAL_PROGRAM'];
        } else if ($action === "draft") {
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
        } else if ($action === "draft" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION")) {
            return $statuses['INTRODUCTION'];
        } else return null;
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
            } else if ($actualStatusCourse === "UNDER_CORRECTION_APPROVAL") {
                return $statuses['PENDING_APPROVAL'];
            } else if ($actualStatusCourse === "UNDER_CORRECTION_PUBLICATION") {
                return $statuses['PENDING_PUBLICATION'];
            }
        } else if ($action === "draft" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION")) {
            return $statuses['INTRODUCTION'];
        } else return null;
    }

    private function checkIsEdition($course_bd)
    {
        return $course_bd->course_origin_uid != null;
    }

    private function updateCourseFieldsNewEdition($request, $courseBd)
    {
        $fields = [
            "inscription_start_date", "inscription_finish_date", "realization_start_date", "realization_finish_date", "min_required_students",
            "presentation_video_url", "lms_url", "lms_system_uid", "cost", "featured_big_carrousel", "featured_big_carrousel_title", "featured_big_carrousel_description",
            "featured_slider_color", "featured_small_carrousel", "validate_student_registrations", "evaluation_criteria", "call_uid"
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

        $image_file_big_carrousel = $request->file('featured_big_carrousel_image_path');

        if ($image_file_big_carrousel) {
            $courseBd->featured_big_carrousel_image_path = saveFile($image_file_big_carrousel, 'images/carrousel-images', null, true);
        }
    }

    private function updateCourseFields($request, $course_bd, $belongsToEducationalProgram)
    {
        // Lista de todos los campos posibles
        $allFields = [
            'title', 'description', 'contact_information', 'course_type_uid', 'educational_program_type_uid',
            'call_uid', 'center_uid', 'objectives', 'ects_workload', 'lms_url', 'lms_system_uid', 'belongs_to_educational_program',
            'inscription_start_date', 'inscription_finish_date',
            'realization_start_date', 'realization_finish_date', 'featured_big_carrousel_description', 'featured_big_carrousel_title',
            'featured_slider_color_font', 'presentation_video_url', 'cost', 'featured_big_carrousel',
            'calification_type', 'enrolling_start_date', 'enrolling_finish_date', 'evaluation_criteria',
            'min_required_students', 'validate_student_registrations', 'featured_big_carrousel_image_path', 'featured_small_carrousel', 'payment_mode'
        ];
        if ($belongsToEducationalProgram) {
            $fields = [
                'title', 'description', 'contact_information', 'course_type_uid', 'educational_program_type_uid',
                'call_uid', 'center_uid', 'objectives', 'ects_workload', 'lms_url', 'lms_system_uid', 'belongs_to_educational_program', 'calification_type',
                'realization_start_date', 'realization_finish_date', 'presentation_video_url'
            ];
        } else {
            $fields = [
                'inscription_start_date', 'inscription_finish_date',
                'realization_start_date', 'realization_finish_date',
                'presentation_video_url', 'featured_big_carrousel', 'featured_big_carrousel_title', 'featured_big_carrousel_description',
                'featured_slider_color', 'featured_slider_color_font', 'evaluation_criteria', 'featured_small_carrousel',
                'calification_type', 'belongs_to_educational_program',
                'title', 'description', 'contact_information', 'course_type_uid', 'educational_program_type_uid',
                'call_uid', 'min_required_students', 'center_uid',
                'objectives', 'ects_workload', 'featured_big_carrousel_image_path',
                'validate_student_registrations', 'lms_url', 'lms_system_uid', 'payment_mode'
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

            $image_file_big_carrousel = $request->file('featured_big_carrousel_image_path');

            if ($image_file_big_carrousel) {
                $course_bd->featured_big_carrousel_image_path = saveFile($image_file_big_carrousel, 'images/carrousel-images', null, true);
            }
        }

        $course_bd->fill($request->only($fields));

        // Establecer a null los campos que no están en la lista de campos a actualizar
        foreach ($allFields as $field) {
            if (!in_array($field, $fields)) {
                $course_bd->$field = null;
            }
        }
    }

    private function updateCarrouselFields($request, $course_bd)
    {

        $belongsToEducationalProgram = $request->input('belongs_to_educational_program');

        // Si el programa formativo no es modular, reseteamos los campos del carrusel
        if ($belongsToEducationalProgram) {
            $this->resetCarrouselFields($course_bd);
        } else {
            $this->updateBigCarrouselFields($request, $course_bd);
            $course_bd->featured_small_carrousel = $request->input('featured_small_carrousel');
        }
    }

    private function resetCarrouselFields($course_bd)
    {
        $course_bd->featured_big_carrousel_title = null;
        $course_bd->featured_big_carrousel_description = null;
        $course_bd->featured_big_carrousel_image_path = null;
        $course_bd->featured_big_carrousel = null;
        $course_bd->featured_small_carrousel = null;
    }

    private function updateBigCarrouselFields($request, $course_bd)
    {
        $featured_big_carrousel = $request->input('featured_big_carrousel');

        if ($featured_big_carrousel) {
            $course_bd->featured_big_carrousel_title = $request->input('featured_big_carrousel_title');
            $course_bd->featured_big_carrousel_description =  $request->input('featured_big_carrousel_description');

            $image_file_big_carrousel = $request->file('featured_big_carrousel_image_path');

            if ($image_file_big_carrousel) {
                $course_bd->featured_big_carrousel_image_path = saveFile($image_file_big_carrousel, 'images/carrousel-images', null, true);
            }
        } else {
            $course_bd->featured_big_carrousel_title = null;
            $course_bd->featured_big_carrousel_description = null;
            $course_bd->featured_big_carrousel_image_path = null;
        }
    }

    private function updateImageField($image_file, $course_bd)
    {
        $path = 'images/courses-images';
        $destinationPath = public_path($path);
        $originalName = pathinfo($image_file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $image_file->getClientOriginalExtension();
        $timestamp = time();

        $filename = "{$originalName}-{$timestamp}.{$extension}";

        $image_file->move($destinationPath, $filename);

        $course_bd->image_path = $path . "/" . $filename;
    }

    private function updateTeachers($request, $course_bd)
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
        $teachers_bd = UsersModel::whereIn('uid', array_keys($teachers))->get()->pluck('uid');

        // Prepare the array for teacher synchronization
        $teachers_to_sync = [];
        foreach ($teachers_bd as $teacher_uid) {
            $teachers_to_sync[$teacher_uid] = [
                'uid' => generate_uuid(),
                'course_uid' => $course_bd->uid,
                'user_uid' => $teacher_uid,
                'type' => $teachers[$teacher_uid]
            ];
        }

        $course_bd->teachers()->sync($teachers_to_sync);
    }

    private function syncCategories($categories, $course_bd)
    {
        $categories_bd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

        CourseCategoriesModel::where('course_uid', $course_bd->uid)->delete();
        $categories_to_sync = [];

        foreach ($categories_bd as $category_uid) {
            $categories_to_sync[] = [
                'uid' => generate_uuid(),
                'course_uid' => $course_bd->uid,
                'category_uid' => $category_uid
            ];
        }

        $course_bd->categories()->sync($categories_to_sync);
    }

    private function syncItemsTags($items, $course_uid)
    {
        $current_tags = CoursesTagsModel::where('course_uid', $course_uid)->pluck('tag')->toArray();

        // Identificar qué items son nuevos y cuáles deben ser eliminados
        $items_to_add = array_diff($items, $current_tags);
        $items_to_delete = array_diff($current_tags, $items);

        // Eliminar los items que ya no son necesarios
        CoursesTagsModel::where('course_uid', $course_uid)->whereIn('tag', $items_to_delete)->delete();

        // Preparar el array para la inserción masiva de nuevos items
        $insertData = [];
        foreach ($items_to_add as $item) {
            $insertData[] = [
                'uid' => generate_uuid(),
                'course_uid' => $course_uid,
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
                    'uid' => generate_uuid(),
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

    private function syncItemsCourseEmails($items, $course_uid)
    {
        $current_emails = CoursesEmailsContactsModel::where('course_uid', $course_uid)->pluck('email')->toArray();

        // Identificar qué items son nuevos y cuáles deben ser eliminados
        $items_to_add = array_diff($items, $current_emails);
        $items_to_delete = array_diff($current_emails, $items);

        // Eliminar los items que ya no son necesarios
        CoursesEmailsContactsModel::where('course_uid', $course_uid)->whereIn('email', $items_to_delete)->delete();

        // Preparar el array para la inserción masiva de nuevos items
        $insertData = [];
        foreach ($items_to_add as $item) {
            $insertData[] = [
                'uid' => generate_uuid(),
                'course_uid' => $course_uid,
                'email' => $item
            ];
        }

        // Insertar todos los nuevos items en una única operación de BD
        CoursesEmailsContactsModel::insert($insertData);
    }

    /**
     * Comprueba si las competencias seleccionadas son correctas.
     * Se comprueba que no se seleccionen dos competencias con el mismo padre si el padre tiene is_multi_select a 0 y
     * que no se seleccione una competencia si no se ha seleccionado su padre.
     */
    private function checkCompetences($allCompetences, $selectedUids)
    {
        // Crea un mapa de competencias para un acceso más rápido
        $competencesMap = [];
        foreach ($allCompetences as $competence) {
            $competencesMap[$competence['uid']] = $competence;
        }

        // Crea un mapa para contar las competencias seleccionadas por padre
        $selectedByParent = [];

        // Verifica cada competencia seleccionada
        foreach ($selectedUids as $uid) {
            $currentCompetence = $competencesMap[$uid];

            // Si la competencia tiene un padre
            if (isset($currentCompetence['parent_competence_uid'])) {
                $parentUid = $currentCompetence['parent_competence_uid'];

                // Si el padre tiene is_multi_select a 0
                if ($competencesMap[$parentUid]['is_multi_select'] == 0) {
                    // Si ya se ha seleccionado otra competencia con el mismo padre, retorna false
                    if (isset($selectedByParent[$parentUid])) {
                        return false;
                    }

                    // Marca que se ha seleccionado una competencia con este padre
                    $selectedByParent[$parentUid] = true;
                }

                // Recorre la jerarquía de competencias padre
                while ($currentCompetence && isset($currentCompetence['parent_competence_uid'])) {
                    // Si la competencia padre no está en la lista de competencias seleccionadas, retorna false
                    if (!in_array($currentCompetence['parent_competence_uid'], $selectedUids)) {
                        return false;
                    }

                    // Avanza a la competencia padre
                    $currentCompetence = $competencesMap[$currentCompetence['parent_competence_uid']];
                }
            }
        }

        // Si todas las competencias seleccionadas pasan la verificación, retorna true
        return true;
    }

    public function getCourseStudents(Request $request, $course_uid)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $course = CoursesModel::where('uid', $course_uid)->first();

        $query = $course->students()->with(
            [
                'courseStudentDocuments' => function ($query) use ($course_uid) {
                    $query->whereHas('courseDocument', function ($query) use ($course_uid) {
                        $query->where('course_uid', $course_uid);
                    });
                },
                'courseStudentDocuments.courseDocument'
            ]
        );

        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw("concat(first_name, ' ', last_name) like ?", ["%$search%"])
                    ->orWhere('nif', 'like', "%$search%");
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

    public function duplicateCourse($course_uid)
    {
        $course_bd = CoursesModel::where('uid', $course_uid)->with(['teachers', 'tags', 'categories'])->first();

        if (!$course_bd) return response()->json(['message' => 'El curso no existe'], 406);

        $new_course = $course_bd->replicate();
        $new_course->title = $new_course->title . " (copia)";

        $introduction_status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $new_course->course_status_uid = $introduction_status->uid;

        DB::transaction(function () use ($new_course, $course_bd) {
            $new_course_uid = generate_uuid();
            $new_course->uid = $new_course_uid;
            $new_course->identifier = $this->generateCourseIdentifier();
            $new_course->creator_user_uid = Auth::user()['uid'];
            $new_course->save();

            $this->duplicateCourseTeachers($course_bd, $new_course_uid, $new_course);

            $this->duplicateCourseTags($course_bd, $new_course_uid);

            $this->duplicateCourseCategories($course_bd, $new_course_uid, $new_course);

            LogsController::createLog('Duplicación de curso', 'Cursos', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => 'Curso duplicado correctamente'], 200);
    }

    private function duplicateCourseTeachers($course_bd, $new_course_uid, $new_course)
    {
        $teachers = $course_bd->teachers->pluck('uid')->toArray();
        $teachers_to_sync = [];

        foreach ($teachers as $teacher_uid) {
            $teachers_to_sync[$teacher_uid] = [
                'uid' => generate_uuid(),
                'course_uid' => $new_course_uid,
                'user_uid' => $teacher_uid
            ];
        }
        $new_course->teachers()->sync($teachers_to_sync);
    }

    private function duplicateCourseTags($course_bd, $new_course_uid)
    {
        $tags = $course_bd->tags->pluck('tag')->toArray();
        $tags_to_add = [];
        foreach ($tags as $tag) {
            $tags_to_add[] = [
                'uid' => generate_uuid(),
                'course_uid' => $new_course_uid,
                'tag' => $tag,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        CoursesTagsModel::insert($tags_to_add);
    }

    private function duplicateCourseCategories($course_bd, $new_course_uid, $new_course)
    {
        $categories = $course_bd->categories->pluck('uid')->toArray();
        $categories_to_sync = [];
        foreach ($categories as $category_uid) {
            $categories_to_sync[] = [
                'uid' => generate_uuid(),
                'course_uid' => $new_course_uid,
                'category_uid' => $category_uid
            ];
        }
        $new_course->categories()->sync($categories_to_sync);
    }

    public function newEditionCourse($course_uid)
    {
        $course_bd = CoursesModel::where('uid', $course_uid)->with([
            'teachers',
            'tags',
            'categories',
            'blocks',
            'blocks.competences',
            'blocks.subBlocks',
            'blocks.subBlocks.elements',
            'blocks.subBlocks.elements.subElements',
        ])->first();

        if (!$course_bd) return response()->json(['message' => 'El curso no existe'], 406);

        $new_course = $course_bd->replicate();
        $new_course->title = $new_course->title . " (nueva edición)";
        $new_course->creator_user_uid = Auth::user()['uid'];
        $introduction_status = CourseStatusesModel::where('code', 'INTRODUCTION')->first();
        $new_course->course_status_uid = $introduction_status->uid;

        return DB::transaction(function () use ($new_course, $course_bd, $course_uid) {
            $new_course_uid = generate_uuid();
            $new_course->uid = $new_course_uid;
            $new_course->identifier = $this->generateCourseIdentifier();
            $new_course->course_origin_uid = $course_uid;
            $new_course->creator_user_uid = Auth::user()['uid'];
            $new_course->save();

            $teachers = $course_bd->teachers->pluck('uid')->toArray();
            $teachers_to_sync = [];

            foreach ($teachers as $teacher_uid) {
                $teachers_to_sync[$teacher_uid] = [
                    'uid' => generate_uuid(),
                    'course_uid' => $new_course_uid,
                    'user_uid' => $teacher_uid
                ];
            }
            $new_course->teachers()->sync($teachers_to_sync);

            $tags = $course_bd->tags->pluck('tag')->toArray();
            $tags_to_add = [];
            foreach ($tags as $tag) {
                $tags_to_add[] = [
                    'uid' => generate_uuid(),
                    'course_uid' => $new_course_uid,
                    'tag' => $tag,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            CoursesTagsModel::insert($tags_to_add);

            $categories = $course_bd->categories->pluck('uid')->toArray();
            $categories_to_sync = [];
            foreach ($categories as $category_uid) {
                $categories_to_sync[] = [
                    'uid' => generate_uuid(),
                    'course_uid' => $new_course_uid,
                    'category_uid' => $category_uid
                ];
            }
            $new_course->categories()->sync($categories_to_sync);

            // Estructura del curso
            foreach ($course_bd->blocks as $block) {
                $newBlock = $block->replicate();
                $newBlock->course_uid = $new_course_uid;
                $newBlock->uid = generate_uuid();
                $newBlock->push();

                // Asignamos las competencias al bloque
                $competences_to_sync = [];
                foreach ($block->competences as $competence) {
                    $competences_to_sync[$competence->uid] = [
                        'uid' => generate_uuid(),
                        'course_block_uid' => $newBlock->uid,
                        'competence_uid' => $competence->uid
                    ];
                }

                $newBlock->competences()->sync($competences_to_sync);

                $learningResultsToSync = [];
                foreach ($block->learningResults as $learningResult) {
                    $learningResultsToSync[$learningResult->uid] = [
                        'uid' => generate_uuid(),
                        'course_block_uid' => $newBlock->uid,
                        'learning_result_uid' => $learningResult->uid
                    ];
                }
                $newBlock->learningResults()->sync($learningResultsToSync);

                foreach ($block->subBlocks as $subBlock) {
                    $newSubBlock = $subBlock->replicate();
                    $newSubBlock->uid = generate_uuid();
                    $newSubBlock->block_uid = $newBlock->uid;
                    $newSubBlock->push();

                    foreach ($subBlock->elements as $element) {
                        $newElement = $element->replicate();
                        $newElement->uid = generate_uuid();
                        $newElement->subblock_uid = $newSubBlock->uid;
                        $newElement->push();

                        foreach ($element->subElements as $subElement) {
                            $newSubElement = $subElement->replicate();
                            $newSubElement->uid = generate_uuid();
                            $newSubElement->element_uid = $newElement->uid;
                            $newSubElement->push();
                        }
                    }
                }
            }

            LogsController::createLog('Nueva edición de curso', 'Cursos', auth()->user()->uid);

            return response()->json(['message' => 'Nueva edición del curso creada correctamente'], 200);
        }, 5);
    }

    private function syncStructure($structure, $course_uid)
    {

        $this->syncDeletedCompositionCourseStructure($course_uid, $structure);

        // Iterar a través de los bloques
        foreach ($structure as $blockData) {

            // Crear o actualizar el bloque
            $block_uid = $blockData['uid'] ?: generate_uuid();
            BlocksModel::updateOrCreate(
                ['uid' => $block_uid],
                [
                    'course_uid' => $course_uid,
                    'type' => $blockData['type'],
                    'name' => $blockData['name'],
                    'description' => $blockData['description'],
                    'order' => $blockData['order']
                ]
            );

            $blockModel = BlocksModel::where('uid', $block_uid)->first();



            $learningResultsToSync = [];
            foreach ($blockData['learningResults'] as $learningResultUid) {
                $learningResultsToSync[$learningResultUid] = [
                    'uid' => generate_uuid(),
                    'course_block_uid' => $block_uid,
                    'learning_result_uid' => $learningResultUid
                ];
            }
            $blockModel->learningResults()->sync($learningResultsToSync);

            if (isset($blockData['subBlocks'])) {
                // Iterar a través de los subbloques
                foreach ($blockData['subBlocks'] as $subBlockData) {
                    // Crear o actualizar el subbloque
                    $subBlock = SubBlocksModel::updateOrCreate(
                        ['uid' => $subBlockData['uid'] ?: generate_uuid()],
                        [
                            'block_uid' => $blockModel['uid'],
                            'name' => $subBlockData['name'],
                            'description' => $subBlockData['description'],
                            'order' => $subBlockData['order']
                        ]
                    );

                    if (isset($subBlockData['elements'])) {
                        // Iterar a través de los elementos
                        foreach ($subBlockData['elements'] as $elementData) {
                            // Crear o actualizar el elemento
                            $element = ElementsModel::updateOrCreate(
                                ['uid' => $elementData['uid'] ?: generate_uuid()],
                                [
                                    'subblock_uid' => $subBlock->uid,
                                    'name' => $elementData['name'],
                                    'description' => $elementData['description'],
                                    'order' => $elementData['order']
                                ]
                            );

                            if (isset($elementData['subElements'])) {
                                // Iterar a través de los subelementos
                                foreach ($elementData['subElements'] as $subElementData) {
                                    // Crear o actualizar el subelemento
                                    SubElementsModel::updateOrCreate(
                                        ['uid' => $subElementData['uid'] ?: generate_uuid()],
                                        [
                                            'element_uid' => $element->uid,
                                            'name' => $subElementData['name'],
                                            'description' => $subElementData['description'],
                                            'order' => $subElementData['order']
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function syncDeletedCompositionCourseStructure($course_uid, $structure)
    {
        // Eliminar cualquier bloque, subbloque, elemento o subelemento que no esté en la lista de uid
        $blocks_uids = collect($structure)->pluck('uid');
        BlocksModel::where('course_uid', $course_uid)->whereNotIn('uid', $blocks_uids)->delete();

        $subblocks_uids = collect($structure)->pluck('subBlocks.*.uid')->flatten();

        if (!$subblocks_uids->contains(null)) {
            SubblocksModel::whereIn('block_uid', $blocks_uids)->whereNotIn('uid', $subblocks_uids)->delete();
        } else {
            SubblocksModel::whereIn('block_uid', $blocks_uids)->delete();
        }

        $elements_uids = collect($structure)->pluck('subBlocks.*.elements.*.uid')->flatten();

        if ($elements_uids->contains(null)) {
            ElementsModel::whereIn('subblock_uid', $subblocks_uids)->whereNotIn('uid', $elements_uids)->delete();
        } else {
            ElementsModel::whereIn('subblock_uid', $subblocks_uids)->delete();
        }

        $subelements_uids = collect($structure)->pluck('subBlocks.*.elements.*.subElements.*.uid')->flatten();

        if ($subelements_uids->contains(null)) {
            SubelementsModel::whereIn('element_uid', $elements_uids)->whereNotIn('uid', $subelements_uids)->delete();
        } else {
            SubelementsModel::whereIn('element_uid', $elements_uids)->delete();
        }
    }

    public function getAllCompetences()
    {
        $competencesLearningResults = CompetencesModel::with('subcompetences')->whereNull('parent_competence_uid')
            ->orderBy('created_at', 'DESC')->get();

        return $competencesLearningResults;
    }
    public function enrollStudents(Request $request)
    {

        $users = $request->get('usersToEnroll');

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
            $enroll->uid = generate_uuid();
            $enroll->course_uid = $request->get('courseUid');
            $enroll->user_uid = $user;
            $enroll->calification_type = "NUMERIC";
            $enroll->acceptance_status = 'ACCEPTED';
            $messageLog = "Alumno añadido a curso";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Cursos', auth()->user()->uid);
            });
        }

        $message = "Alumnos añadidos al curso";

        if ($usersenrolled == true) {
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
        $course_uid = $request->get('course_uid');

        $reader = Reader::createFromPath($file->path());

        foreach ($reader as $key => $row) {

            if ($key > 0) {

                $existingUser = UsersModel::where('email', $row[3])
                    ->first();

                if ($existingUser) {
                    $this->enrollUserCsv($row, $existingUser->uid, $course_uid);
                } else {
                    $this->validateUserCsv($row, $key);
                    $this->singUpUser($row, $course_uid);
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

    public function singUpUser($row, $course_uid)
    {

        $newUserUid = generate_uuid();

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

        $this->enrollUserCsv($row, $newUserUid, $course_uid);
    }
    public function enrollUserCsv($row, $user_uid, $course_uid)
    {

        $existingEnrollment = CoursesStudentsModel::where('course_uid', $course_uid)
            ->where('user_uid', $user_uid)
            ->first();

        if (!$existingEnrollment) {

            $enroll = new CoursesStudentsModel();
            $enroll->uid = generate_uuid();
            $enroll->course_uid = $course_uid;
            $enroll->user_uid = $user_uid;
            $enroll->calification_type = "NUMERIC";
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
        $identifier = 'CUR-' . str_pad($coursesCount + 1, 4, '0', STR_PAD_LEFT);
        return $identifier;
    }
}
