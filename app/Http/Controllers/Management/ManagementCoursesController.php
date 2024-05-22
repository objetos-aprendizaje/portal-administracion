<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Logs\LogsController;
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
use App\Models\CoursesStudentsModel;
use App\Models\CoursesTagsModel;

use Illuminate\Support\Facades\DB;

use App\Models\CourseStatusesModel;
use App\Models\CoursesUsersModel;
use App\Models\EducationalProgramsModel;
use App\Models\ElementsModel;
use App\Models\GeneralOptionsModel;
use App\Models\NotificationsChangesStatusesCoursesModel;
use App\Models\SubblocksModel;
use App\Models\SubelementsModel;
use App\Models\UserRolesModel;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use League\Csv\Reader;
use League\Csv\Statement;

use App\Rules\NifNie;

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

        if (!empty($categories)) $categories = $this->buildNestedCategories($categories);

        $variables_js = [
            "operationByCalls" => GeneralOptionsModel::where(['option_name' => 'operation_by_calls'])->first()->option_value == 1,
            "competences" => $competences
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
                "tabulator" => true,
                "tomselect" => true,
                "flatpickr" => true,
                "educational_programs" => $educational_programs,
                "variables_js" => $variables_js,
                "treeselect" => true,
                "centers" => $centers
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
            return response()->json(['message' => 'No se han enviado los datos correctamente'], 406);
        }

        // Obtenemos los cursos de la base de datos
        $courses_bd = CoursesModel::whereIn('uid', array_column($changesCoursesStatuses, "uid"))->with('status')->get()->toArray();

        // Excluímos los estados a los que no se pueden cambiar manualmente.
        $statuses_courses = CourseStatusesModel::whereNotIn('code', ['INSCRIPTION', 'DEVELOPMENT', 'PENDING_INSCRIPTION', 'FINISHED'])->get()->toArray();

        // Aquí iremos almacenando los datos de los cursos que se van a actualizar
        $updated_courses_data = [];

        // Recorremos los cursos que nos vienen en el request y los comparamos con los de la base de datos
        foreach ($changesCoursesStatuses as $changeCourseStatus) {

            // Obtenemos el curso de la base de datos
            $course_bd = findOneInArray($courses_bd, 'uid', $changeCourseStatus['uid']);

            // Si no existe el curso en la base de datos, devolvemos un error
            if (!$course_bd) {
                return response()->json(['message' => 'Uno de los cursos no existe'], 406);
            }

            // Le cambiamos a cada curso el estado que nos viene en el request
            $status_bd = findOneInArray($statuses_courses, 'code', $changeCourseStatus['status']);

            if (!$status_bd) {
                return response()->json(['message' => 'El estado es incorrecto'], 406);
            }

            $updated_courses_data[] = [
                'uid' => $course_bd['uid'],
                'course_status_uid' => $status_bd['uid'],
                'reason' => $changeCourseStatus['reason'] ?? null
            ];

            $notification_change_status_course = new NotificationsChangesStatusesCoursesModel();
            $notification_change_status_course->uid = generate_uuid();
            $notification_change_status_course->user_uid = $course_bd['creator_user_uid'];
            $notification_change_status_course->course_uid = $course_bd['uid'];
            $notification_change_status_course->course_status_uid = $status_bd['uid'];
            $notification_change_status_course->date = date('Y-m-d H:i:s');

            $notification_change_status_course->save();
        }

        DB::transaction(function () use ($updated_courses_data) {
            // Guardamos en la base de datos los cambios
            foreach ($updated_courses_data as $data) {
                CoursesModel::updateOrInsert(
                    ['uid' => $data['uid']],
                    [
                        'course_status_uid' => $data['course_status_uid'],
                        'status_reason' => $data['reason']
                    ]
                );
            }

            LogsController::createLog('Cambio de estado de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Se han actualizado los estados de los cursos correctamente'], 200);
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
            $query = $this->buildQueryForAdminOrManagement();
        } else if (in_array("TEACHER", $roles)) {
            $query = $this->buildQueryForTeacher();
        }

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%")
                ->orWhere('courses.uid', $search);
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

    private function buildQueryForAdminOrManagement()
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
            ->select('courses.*',
            'status.name as status_name',
            'status.code as status_code',
            'calls.name as calls_name',
            'educational_programs.name as educational_programs_name',
            'educational_program_types.name as educational_program_types_name',
            'course_types.name as course_types_name',
            'centers.name as centers_name',);
    }

    private function buildQueryForTeacher()
    {
        $userUid = Auth::user()['uid'];

        return CoursesModel::query()
            ->leftJoin('course_statuses as status', 'courses.course_status_uid', '=', 'status.uid')
            ->leftJoin('calls as calls', 'courses.call_uid', '=', 'calls.uid')
            ->leftJoin('educational_programs as educational_programs', 'courses.educational_program_uid', '=', 'educational_programs.uid')
            ->leftJoin('educational_program_types as educational_program_types', 'courses.educational_program_type_uid', '=', 'educational_program_types.uid')
            ->leftJoin('course_types as course_types', 'courses.course_type_uid', '=', 'course_types.uid')
            ->leftJoin('centers as centers', 'courses.center_uid', '=', 'centers.uid')
            ->leftJoin('courses_teachers as ct', 'courses.uid', '=', 'ct.course_uid')
            ->with('tags')
            ->with('contact_emails')
            ->with('teachers_coordinate')
            ->with('teachers_no_coordinate')
            ->with('categories')
            ->select('courses.*',
            'status.name as status_name',
            'status.code as status_code',
            'calls.name as calls_name',
            'educational_programs.name as educational_programs_name',
            'educational_program_types.name as educational_program_types_name',
            'course_types.name as course_types_name',
            'centers.name as centers_name',)
            ->where(function ($query) use ($userUid) {
                $query->where('courses.creator_user_uid', '=', $userUid)
                    ->orWhere('ct.user_uid', '=', $userUid);
            });
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
            'blocks' => function ($query) {
                $query->orderBy('order', 'asc');
            },
            'blocks.competences',
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
            'center'
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
            $course_bd = CoursesModel::where('uid', $course_uid)->first();
        } else {
            $isNew = true;
            $course_bd = new CoursesModel();
            $course_bd->uid = generate_uuid();
        }

        $validator = $this->validateCourse($request, $course_bd);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        // Si el curso no es nuevo, el usuario sólo tiene el rol de docente y no es el creador del curso
        $hasPermissions = $this->checkPermissions($course_bd, $isNew);
        if (!$hasPermissions) {
            return response()->json(['message' => 'No tienes permisos para editar este curso'], 403);
        }

        $action = $request->input('action');
        $new_course_status = $this->checkNewStatus($action, $isNew, $course_bd);
        if ($new_course_status) $course_bd->course_status_uid = $new_course_status->uid;

        return DB::transaction(function () use ($request, $course_bd, $isNew) {

            // Comprobamos si el curso es una nueva edición. En función de esto, se actualizarán o no ciertos campos
            $this->updateCourseFields($request, $course_bd);
            $this->updateCarrouselFields($request, $course_bd);

            $image_file = $request->file('image_input_file');
            if ($image_file) $this->updateImageField($image_file, $course_bd);

            $course_bd->save();

            if (!$this->checkIsEdition($course_bd)) {
                // Guardado de profesores
                $this->updateTeachers($request, $course_bd);

                // Tags
                $tags = json_decode($request->input('tags'), true);
                $this->syncItemsTags($tags, $course_bd->uid);

                // Emails de contacto
                $contact_emails = json_decode($request->input('contact_emails'), true);
                $this->syncItemsCourseEmails($contact_emails, $course_bd->uid);

                // Categorías
                $categories = $request->input('categories');
                $categories = json_decode($categories, true);
                $this->syncCategories($categories, $course_bd);

                // Estructura
                $structure = $request->input('structure');
                $structure = json_decode($structure, true);
                $this->syncStructure($structure, $course_bd->uid);
            }
            // documentos
            $documents = $request->input('documents');
            $documents = json_decode($documents, true);
            $course_bd->updateDocuments($documents);

            LogsController::createLog(($isNew) ? 'Curso añadido' : 'Curso actualizado', 'Cursos', auth()->user()->uid);

            return response()->json(['message' => ($isNew) ? 'Se ha añadido el curso correctamente' : 'Se ha actualizado el curso correctamente'], 200);
        }, 5);
    }

    private function validateCourse(Request $request, $course_bd)
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
            'realization_start_date.date_format' => 'La fecha de inicio de realización no tiene el formato correcto.',
            'realization_finish_date.required' => 'La fecha de finalización de realización es obligatoria.',
            'realization_finish_date.date_format' => 'La fecha de finalización de realización no tiene el formato correcto.',

            'presentation_video_url.url' => 'Introduce una URL válida para el video de presentación.',
            'ects_workload.required' => 'La carga de trabajo ECTS es obligatoria.',
            'ects_workload.numeric' => 'La carga de trabajo ECTS debe ser un número.',
            'validate_student_registrations.required' => 'Indica si se validarán las inscripciones de los estudiantes.',
            'lms_url.url' => 'Introduce una URL válida para el LMS.',
            'call_uid.required' => 'Selecciona la convocatoria del curso.',
            'center_uid' => 'Debes especificar un centro',
            'featured_big_carrousel_title.required' => 'Debes especificar un título',
            'featured_big_carrousel_description.required' => 'Debes especificar una descripción',
            'evaluation_criteria.required' => 'Debes especificar unos criterios de evaluación si activas la validación de estudiantes',
            'realization_start_date.after_or_equal' => 'La fecha de inicio de realización no puede ser anterior a la fecha de fin de inscripción.',
            'realization_finish_date.after_or_equal' => 'La fecha de finalización de realización no puede ser anterior a la fecha de inicio de realización.',
            'calification_type.required' => 'Debes especificar un tipo de calificación',
            'min_required_students.min' => 'El número mínimo de estudiantes no puede ser negativo.',
            'enrolling_start_date.required' => 'La fecha de inicio de matriculación es obligatoria.',
            'enrolling_finish_date.required' => 'La fecha de fin de matriculación es obligatoria.',
            'enrolling_start_date.after_or_equal' => 'La fecha de inicio de matriculación no puede ser anterior a la de fin de inscripción.',
            'enrolling_finish_date.before_or_equal' => 'La fecha de fin de matriculación no puede ser posterior a la de inicio de realización.'
        ];


        $featured_big_carrousel = $request->input('featured_big_carrousel');
        $validate_student_registrations = $request->input('validate_student_registrations');

        if ($featured_big_carrousel) {
            // Comprobamos el número de cursos con el carrusel grande activado
            $countCoursesFeaturedBigCarrousel = CoursesModel::where('featured_big_carrousel', 1)->count();
            if ($countCoursesFeaturedBigCarrousel > 9) {
                return response()->json(['message' => 'Ya tienes destacados más de 10 cursos en el carrousel grande'], 422);
            }
        }

        $featured_small_carrousel = $request->input('featured_small_carrousel');
        if ($featured_small_carrousel) {
            // Comprobamos el número de cursos con el carrusel pequeño activado
            $countCoursesFeaturedSmallCarrousel = CoursesModel::where('featured_small_carrousel', 1)->count();

            if ($countCoursesFeaturedSmallCarrousel > 9) {
                return response()->json(['message' => 'Ya tienes destacados más de 10 cursos en el carrousel pequeño'], 422);
            }
        }

        $rules = [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'contact_information' => 'nullable|string',
            'course_type_uid' => 'required|string',
            'educational_program_type_uid' => 'required|string',
            'min_required_students' => 'nullable|integer',
            'realization_start_date' => 'required|date_format:"Y-m-d\TH:i"',
            'realization_finish_date' => 'required|date_format:"Y-m-d\TH:i"',
            'presentation_video_url' => 'nullable|url',
            'objectives' => 'nullable|string',
            'ects_workload' => 'required|numeric',
            'validate_student_registrations' => 'required|boolean',
            'lms_url' => 'nullable|url',
            'cost' => 'nullable|numeric',
            'featured_big_carrousel_title' => $featured_big_carrousel ? 'required|string' : 'nullable|string',
            'featured_big_carrousel_description' => $featured_big_carrousel ? 'required|string' : 'nullable|string',
            'center_uid' => 'required|string',
            'evaluation_criteria' => $validate_student_registrations ? 'required|string' : 'nullable|string',
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
            'enrolling_start_date' => 'required|after_or_equal:inscription_finish_date',
            'enrolling_finish_date' => 'required|before_or_equal:realization_start_date',
        ];

        // Si no tenemos programa educativo, validamos las fechas de inscripción
        if (!$request->input('educational_program_uid')) {
            $rules['inscription_start_date'] = 'required|after_or_equal:now';
            $rules['inscription_finish_date'] = 'required|after_or_equal:inscription_start_date';
        }

        $operation_by_calls = GeneralOptionsModel::where(['option_name' => 'operation_by_calls'])->first()->option_value;

        if ($operation_by_calls) $rules['call_uid'] = 'required|string';
        else $rules['call_uid'] = 'nullable|string';

        $course_uid = $request->input('course_uid');

        $isNew = $course_uid ? false : true;

        $educational_program_uid = $request->input('educational_program_uid');

        // Si tenemos programa educativo, comprobamos que la fecha de realización sea posterior a la de inscripción del programa
        if ($educational_program_uid) {
            $educational_program = EducationalProgramsModel::where('uid', $educational_program_uid)->first();

            $rules['realization_start_date'] = ['required', 'date_format:"Y-m-d\TH:i"', function ($attribute, $value, $fail) use ($educational_program) {
                if ($value < $educational_program->inscription_finish_date) {
                    $fail('La fecha de realización no puede ser anterior a las fechas de inscripción del programa formativo');
                }
            }];
        }

        // Si el curso es nuevo, establecemos el creador y el uid
        if ($isNew) {
            $course_uid = generate_uuid();
            $course_bd->uid = $course_uid;
            $course_bd->creator_user_uid = Auth::user()['uid'];

            $rules['realization_start_date'] = 'required|after_or_equal:inscription_finish_date';
            $rules['realization_finish_date'] = 'required|after_or_equal:realization_start_date';
        }




        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator;
    }

    private function checkPermissions($course_bd, $isNew)
    {
        $user_roles = array_column(Auth::user()['roles']->toArray(), "code");

        // Si el curso no es nuevo, el usuario sólo tiene el rol de docente y no es el creador del curso
        $isOnlyTeacher = count($user_roles) == 1 && in_array("TEACHER", $user_roles);
        $isCourseCreator = $course_bd->creator_user_uid == Auth::user()['uid'];

        if (!$isNew && $isOnlyTeacher && !$isCourseCreator) {
            return false;
        }

        return true;
    }

    private function checkNewStatus($action, $isNew, $course_bd)
    {

        $pending_publication = CourseStatusesModel::where('code', 'PENDING_PUBLICATION')->first();
        $pending_approval = CourseStatusesModel::where('code', 'PENDING_APPROVAL')->first();
        $introduction = CourseStatusesModel::where('code', 'INTRODUCTION')->first();


        if (!$isNew) {
            $actual_status_course = $course_bd->status->code;

            if (!in_array($actual_status_course, ['INTRODUCTION', 'UNDER_CORRECTION_APPROVAL', 'UNDER_CORRECTION_PUBLICATION'])) abort(403);

            switch ($actual_status_course) {
                case 'UNDER_CORRECTION_PUBLICATION':
                    $new_course_status = $pending_publication;
                    break;
                case 'UNDER_CORRECTION_APPROVAL':
                    $new_course_status = $pending_approval;
                    break;
                case 'INTRODUCTION':
                    $new_course_status = $action == "submit" ? $pending_approval : null;
                    break;
                default:
                    $new_course_status = $introduction;
                    break;
            }
        } else {
            $new_course_status = $action == "submit" ? $pending_approval : $introduction;
        }

        return $new_course_status;
    }

    private function checkIsEdition($course_bd)
    {
        return $course_bd->course_origin_uid != null;
    }

    private function updateCourseFields($request, $course_bd)
    {
        // Comprobamos si el curso es una nueva edición. En función de esto, se actualizarán o no ciertos campos
        $isEdition = $this->checkIsEdition($course_bd);

        $fields = [
            'inscription_start_date', 'inscription_finish_date',
            'realization_start_date', 'realization_finish_date',
            'presentation_video_url', 'cost', 'featured_big_carrousel',
            'calification_type'
        ];

        if (!$isEdition) {
            $additionalFields = [
                'title', 'description', 'contact_information', 'course_type_uid', 'educational_program_type_uid',
                'call_uid', 'min_required_students', 'center_uid',
                'objectives', 'ects_workload',
                'validate_student_registrations', 'lms_url', 'educational_program_uid',
            ];

            $fields = array_merge($fields, $additionalFields);
        }

        $course_bd->fill($request->only($fields));
    }

    private function updateCarrouselFields($request, $course_bd)
    {
        // Obtenemos el programa formativo
        $educational_program_uid = $request->input('educational_program_uid');
        $educational_program = EducationalProgramsModel::where('uid', $educational_program_uid)->first();

        // Si el programa formativo no es modular, reseteamos los campos del carrusel
        if ($educational_program && !$educational_program->is_modular) {
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
        $course_bd->featured_big_carrousel = 0;
        $course_bd->featured_small_carrousel = 0;
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
                if ($order['field'] == 'approved') {
                    $query->join('courses_students', function ($join) use ($course_uid) {
                        $join->on('users.uid', '=', 'courses_students.user_uid')
                            ->where('courses_students.course_uid', '=', $course_uid);
                    })->orderBy('courses_students.approved', $order['dir']);
                } else {
                    $query->orderBy($order['field'], $order['dir']);
                }
            }
        }

        // Aplicar paginación
        $students = $query->paginate($size);

        return response()->json($students, 200);
    }

    public function saveCourseStudents(Request $request, $course_uid)
    {

        $students = $request->input('students');
        $students = json_decode($students, true);

        DB::transaction(function () use ($students, $course_uid) {
            foreach ($students as $student) {

                $user = CoursesUsersModel::where('user_uid', $student)->where('course_uid', $course_uid)->first();

                if ($user == null) {

                    //Rol de estudiante
                    $student_rol = UserRolesModel::where("code", "STUDENT")->first();

                    $courseUser = new CoursesUsersModel();
                    $courseUser->uid = generate_uuid();
                    $courseUser->course_uid = $course_uid;
                    $courseUser->user_uid = $student;
                    $courseUser->user_rol_uid = $student_rol['uid'];
                    $courseUser->save();
                }
            }

            LogsController::createLog('Estudiantes añadidos al curso', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Añadidos estudiantes al curso'], 200);
    }

    public function deleteCourseStudents(Request $request)
    {

        $uids = $request->input('uids');

        DB::transaction(function () use ($uids) {
            CoursesUsersModel::destroy($uids);
            LogsController::createLog('Eliminar estudiantes de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Estudiantes del curso eliminados correctamente'], 200);
    }

    public function approveInscriptionsCourse(Request $request)
    {

        $selectedCourseStudents = $request->input('uids');

        DB::transaction(function () use ($selectedCourseStudents) {
            CoursesStudentsModel::whereIn('uid', $selectedCourseStudents)->update(['approved' => 1]);
            LogsController::createLog('Aprobación de cursos', 'Cursos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Inscripciones aprobadas correctamente'], 200);
    }

    public function rejectInscriptionsCourse(Request $request)
    {
        $selectedCourseStudents = $request->input('uids');

        DB::transaction(function () use ($selectedCourseStudents) {
            CoursesStudentsModel::whereIn('uid', $selectedCourseStudents)->update(['approved' => 0]);
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

        return DB::transaction(function () use ($new_course, $course_bd) {
            $new_course_uid = generate_uuid();
            $new_course->uid = $new_course_uid;

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

            LogsController::createLog('Duplicación de curso', 'Cursos', auth()->user()->uid);

            return response()->json(['message' => 'Curso duplicado correctamente'], 200);
        }, 5);
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
            $new_course->course_origin_uid = $course_uid;
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

        $all_competences = CompetencesModel::all()->toArray();

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

            $competences_correct = $this->checkCompetences($all_competences, $blockData['competences']);
            if (!$competences_correct) {
                return response()->json(['message' => 'Las competencias seleccionadas no son correctas'], 422);
            }

            $competences_to_sync = [];
            foreach ($blockData['competences'] as $competence_uid) {
                $competences_to_sync[$competence_uid] = [
                    'uid' => generate_uuid(),
                    'course_block_uid' => $block_uid,
                    'competence_uid' => $competence_uid
                ];
            }
            $blockModel->competences()->sync($competences_to_sync);

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
        $competences = CompetencesModel::whereNull('parent_competence_uid')->with('subcompetences')->get()->toArray();

        return response()->json($competences, 200);
        $competences = CompetencesModel::with('parentCompetence')->get()->toArray();

        return response()->json($competences, 200);
    }
    public function enrollStudents(Request $request){

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
            $enroll->approved = 1;
            $messageLog = "Alumno añadido a curso";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Cursos', auth()->user()->uid);
            });
        }

        $message = "Alumnos añadidos al curso";

        if ($usersenrolled == true){
            $message = "Alumnos añadidos al curso. Los ya registrados no se han añadido.";
        }

        return response()->json(['message' => $message], 200);

    }

    public function enrollStudentsCsv(Request $request){

        $file = $request->file('attachment');
        $course_uid = $request->get('course_uid');

        $reader = Reader::createFromPath($file->path());

        $user = false;

        foreach ($reader as $key => $row) {

            if ($key > 0){

                $validatorNif = Validator::make(
                    ['tu_dato' => $row[2]],
                    ['tu_dato' => [new NifNie]] // Aplicar la regla NifNie al dato
                );
                if ($validatorNif->fails()) {
                    continue;
                }
                $reglas = [
                    'correo' => 'email', // La regla 'email' valida que sea un correo electrónico válido
                ];
                $validatorEmail = Validator::make(['correo' => $row[3]], $reglas);

                if ($validatorEmail->fails()) {
                    continue;
                }

                $existingUser = UsersModel::where('email', $row[3])
                ->first();



                if ($existingUser) {

                    $this->enrollUserCsv($row,$existingUser->uid,$course_uid);

                }else{

                    $this->singUpUser($row,$course_uid);

                }

            }

        }

        $message = "Alumnos añadidos al curso. Los ya registrados no se han añadido.";

        return response()->json(['message' => $message], 200);

    }
    public function singUpUser($row,$course_uid){

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

        $this->enrollUserCsv($row,$newUserUid,$course_uid);

    }
    public function enrollUserCsv($row,$user_uid,$course_uid){

        $usersenrolled = false;

        $existingEnrollment = CoursesStudentsModel::where('course_uid', $course_uid)
            ->where('user_uid', $user_uid)
            ->first();

        if (!$existingEnrollment) {

            $enroll = new CoursesStudentsModel();
            $enroll->uid = generate_uuid();
            $enroll->course_uid = $course_uid;
            $enroll->user_uid = $user_uid;
            $enroll->calification_type = "NUMERIC";
            $enroll->approved = 1;
            $messageLog = "Alumno añadido a curso";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Cursos', auth()->user()->uid);
            });
        }
    }
}
