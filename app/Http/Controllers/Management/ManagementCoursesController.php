<?php

namespace App\Http\Controllers\Management;

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
use App\Models\CompetencesModel;
use App\Models\CourseCategoriesModel;
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

        $teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->with('roles')->get()->toArray();

        $students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })->with('roles')->get()->toArray();

        $categories = CategoriesModel::with('parentCategory')->get()->toArray();

        $educational_programs = EducationalProgramsModel::all()->toArray();

        if (!empty($categories)) $categories = $this->buildNestedCategories($categories);

        $js_variables = [
            "operationByCalls" => GeneralOptionsModel::where(['option_name' => 'operation_by_calls'])->first()->option_value == 1,
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
                "js_variables" => $js_variables
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
        $statuses_courses = CourseStatusesModel::all()->toArray();

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

        if (in_array('ADMINISTRATOR', $roles) || in_array('MANAGEMENT', $roles)) {
            $query = CoursesModel::query()
                ->join('course_statuses as status', 'courses.course_status_uid', '=', 'status.uid')
                ->select('courses.*', 'status.name as status_name')
                ->with('status');
        } else if (in_array("TEACHER", $roles)) {
            $userUid = Auth::user()['uid'];

            $query = CoursesModel::query()
                ->join('course_statuses as status', 'courses.course_status_uid', '=', 'status.uid')
                ->leftJoin('courses_teachers as ct', 'courses.uid', '=', 'ct.course_uid')
                ->select('courses.*', 'status.name as status_name')
                ->where(function ($query) use ($userUid) {
                    $query->where('courses.creator_user_uid', '=', $userUid)
                        ->orWhere('ct.user_uid', '=', $userUid);
                })
                ->with('status');
        }

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if ($filter['database_field'] == "center") {
                    $query->where("center", 'LIKE', "%{$filter['value']}%");
                } elseif ($filter['database_field'] == 'inscription_date') {
                    $query->where(function ($query) use ($filter) {
                        $query->whereBetween('inscription_start_date', [$filter['value'][0], $filter['value'][1]])
                            ->orWhereBetween('inscription_finish_date', [$filter['value'][0], $filter['value'][1]]);
                    });
                } elseif ($filter['database_field'] == 'realization_date') {
                    $query->where(function ($query) use ($filter) {
                        $query->whereBetween('realization_start_date', [$filter['value'][0], $filter['value'][1]])
                            ->orWhereBetween('realization_finish_date', [$filter['value'][0], $filter['value'][1]]);
                    });
                } elseif ($filter['database_field'] == "teachers") {

                    $teachers_uids = $filter['value'];
                    $query->whereHas('teachers', function ($query) use ($teachers_uids) {
                        $query->whereIn('courses_teachers.uid', $teachers_uids);
                    });
                } elseif ($filter['database_field'] == 'creator_user_uid') {

                    $query->whereIn('creator_user_uid', $filter['value']);
                } elseif ($filter['database_field'] == 'categories') {

                    $categories_uids = $filter['value'];
                    $query->whereHas('categories', function ($query) use ($categories_uids) {
                        $query->whereIn('categories.uid', $categories_uids);
                    });
                } else {
                    $query->where($filter['database_field'], $filter['value']);
                }
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
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

        $messages = [
            'title.required' => 'Introduce el título del curso.',
            'course_type_uid.required' => 'Selecciona el tipo de curso.',
            'educational_program_type_uid.required' => 'Selecciona el tipo de programa educativo.',
            'min_required_students.integer' => 'El número mínimo de estudiantes debe ser un número entero.',

            'inscription_start_date.required' => 'La fecha de inicio de inscripción es obligatoria.',
            'inscription_start_date.date_format' => 'La fecha de inicio de realización no tiene el formato correcto.',
            'inscription_finish_date.required' => 'La fecha de fin de inscripción es obligatoria.',
            'inscription_finish_date.date_format' => 'La fecha de fin de inscripción no tiene el formato correcto.',

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
            'center' => 'Debes especificar un centro',
            'featured_big_carrousel_title.required' => 'Debes especificar un título',
            'featured_big_carrousel_description.required' => 'Debes especificar una descripción',
            'evaluation_criteria.required' => 'Debes especificar unos criterios de evaluación si activas la validación de estudiantes',
            'inscription_start_date.after_or_equal' => 'La fecha de inicio de inscripción no puede ser anterior a la fecha y hora actual.',
            'inscription_finish_date.after_or_equal' => 'La fecha de fin de inscripción no puede ser anterior a la fecha de inicio de inscripción.',
            'realization_start_date.after_or_equal' => 'La fecha de inicio de realización no puede ser anterior a la fecha de fin de inscripción.',
            'realization_finish_date.after_or_equal' => 'La fecha de finalización de realización no puede ser anterior a la fecha de inicio de realización.',
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
            'course_type_uid' => 'required|string',
            'educational_program_type_uid' => 'required|string',
            'min_required_students' => 'nullable|integer',
            'center' => 'nullable|string',
            'inscription_start_date' => 'required|date_format:"Y-m-d\TH:i"',
            'inscription_finish_date' => 'required|date_format:"Y-m-d\TH:i"',
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
            'center' => 'required|string',
            'evaluation_criteria' => $validate_student_registrations ? 'required|string' : 'nullable|string',
        ];

        $operation_by_calls = GeneralOptionsModel::where(['option_name' => 'operation_by_calls'])->first()->option_value;

        if ($operation_by_calls) $rules['call_uid'] = 'required|string';
        else $rules['call_uid'] = 'nullable|string';

        $course_uid = $request->input('course_uid');

        $isNew = $course_uid ? false : true;
        $course_bd = $course_uid ? CoursesModel::where('uid', $course_uid)->first() : new CoursesModel();

        if ($isNew) {
            $course_uid = generate_uuid();
            $course_bd->uid = $course_uid;
            $course_bd->creator_user_uid = Auth::user()['uid'];

            $rules['inscription_start_date'] = 'after_or_equal:now';
            $rules['inscription_finish_date'] = 'after_or_equal:inscription_start_date';
            $rules['realization_start_date'] = 'after_or_equal:inscription_finish_date';
            $rules['realization_finish_date'] = 'after_or_equal:realization_start_date';
        } else {
            // Comprobamos si hay algún cambio de fecha en las fechas de inscripción y realización del curso y en base a eso, las validamos
            $inscription_start_date = $request->input('inscription_start_date');
            $inscription_finish_date = $request->input('inscription_finish_date');
            $realization_start_date = $request->input('realization_start_date');
            $realization_finish_date = $request->input('realization_finish_date');

            if (strtotime($course_bd->inscription_start_date) != strtotime($inscription_start_date)) {
                $rules['inscription_start_date'] = 'after_or_equal:now';
            } else if (strtotime($course_bd->inscription_finish_date) != strtotime($inscription_finish_date)) {
                $rules['inscription_finish_date'] = 'after_or_equal:inscription_start_date';
            } else if (strtotime($course_bd->realization_start_date) != strtotime($realization_start_date)) {
                $rules['realization_start_date'] = 'after_or_equal:inscription_finish_date';
            } else if (strtotime($course_bd->realization_finish_date) != strtotime($realization_finish_date)) {
                $rules['realization_finish_date'] = 'after_or_equal:realization_start_date';
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $user_roles = array_column(Auth::user()['roles']->toArray(), "code");

        // Si el curso no es nuevo, el usuario sólo tiene el rol de docente y no es el creador del curso
        $isOnlyTeacher = count($user_roles) == 1 && in_array("TEACHER", $user_roles);
        $isCourseCreator = $course_bd->creator_user_uid == Auth::user()['uid'];

        if (!$isNew && $isOnlyTeacher && !$isCourseCreator) {
            return response()->json(['message' => 'No tienes permisos para editar este curso'], 403);
        }

        // Comprobamos el nuevo estado que le corresponde al curso.
        $action = $request->input('action');

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

        if ($new_course_status) $course_bd->course_status_uid = $new_course_status->uid;

        return DB::transaction(function () use ($request, $course_bd, $isNew, $course_uid) {

            // Comprobamos si el curso es una nueva edición. En función de esto, se actualizarán o no ciertos campos
            $isEdition = $course_bd->course_origin_uid != null;

            $fields = [
                'inscription_start_date', 'inscription_finish_date',
                'realization_start_date', 'realization_finish_date',
                'presentation_video_url', 'cost', 'featured_big_carrousel', 'featured_small_carrousel'
            ];

            if (!$isEdition) {
                $additionalFields = [
                    'title', 'description', 'course_type_uid', 'educational_program_type_uid',
                    'call_uid', 'min_required_students', 'center',
                    'objectives', 'ects_workload',
                    'validate_student_registrations', 'lms_url', 'educational_program_uid',
                ];

                $fields = array_merge($fields, $additionalFields);
            }

            $course_bd->fill($request->only($fields));

            $featured_big_carrousel = $request->input('featured_big_carrousel');

            // Carrousel grande. Si ha habilitado el check, recogemos las propiedades correspondientes. En caso contrario, las establecemos a null
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

            if ($request->file('image_input_file')) {

                $file = $request->file('image_input_file');
                $path = 'images/courses-images';
                $destinationPath = public_path($path);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $timestamp = time();

                $filename = "{$originalName}-{$timestamp}.{$extension}";

                $file->move($destinationPath, $filename);

                $course_bd->image_path = $path . "/" . $filename;
            }

            $course_bd->save();

            if (!$isEdition) {
                // Guardado de profesores
                $teachers = $request->input('teachers');
                $teachers = json_decode($teachers, true);

                // Sacamos los profesores actuales del curso
                $teachers_bd = UsersModel::whereIn('uid', $teachers)->get()->pluck('uid');

                // Preparamos el array para la sincronización de profesores
                $teachers_to_sync = [];
                foreach ($teachers_bd as $teacher_uid) {
                    $teachers_to_sync[$teacher_uid] = [
                        'uid' => generate_uuid(),
                        'course_uid' => $course_uid,
                        'user_uid' => $teacher_uid
                    ];
                }
                $course_bd->teachers()->sync($teachers_to_sync);

                // Tags
                $tags = $request->input('tags');
                $tags = json_decode($tags, true);

                // Verificar si hay tags
                if (!empty($tags)) {
                    // Obtener los tags actuales del curso desde la BD
                    $current_tags = CoursesTagsModel::where('course_uid', $course_uid)->pluck('tag')->toArray();

                    // Identificar qué tags son nuevos y cuáles deben ser eliminados
                    $tags_to_add = array_diff($tags, $current_tags);
                    $tags_to_delete = array_diff($current_tags, $tags);

                    // Eliminar los tags que ya no son necesarios
                    CoursesTagsModel::where('course_uid', $course_bd->uid)->whereIn('tag', $tags_to_delete)->delete();

                    // Preparar el array para la inserción masiva de nuevos tags
                    $insertData = [];
                    foreach ($tags_to_add as $tag) {
                        $insertData[] = [
                            'uid' => generate_uuid(),
                            'course_uid' => $course_uid,
                            'tag' => $tag
                        ];
                    }

                    // Insertar todos los nuevos tags en una única operación de BD
                    CoursesTagsModel::insert($insertData);
                } else {
                    // Si no hay tags, eliminar todos los tags asociados a este curso
                    CoursesTagsModel::where('course_uid', $course_uid)->delete();
                }

                // Categorías
                $categories = $request->input('categories');
                $categories = json_decode($categories, true);

                $categories_bd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

                CourseCategoriesModel::where('course_uid', $course_uid)->delete();

                $categories_to_sync = [];
                foreach ($categories_bd as $category_uid) {
                    $categories_to_sync[] = [
                        'uid' => generate_uuid(),
                        'course_uid' => $course_uid,
                        'category_uid' => $category_uid
                    ];
                }

                $course_bd->categories()->sync($categories_to_sync);

                // Estructura
                $structure = $request->input('structure');
                $structure = json_decode($structure, true);
                $this->syncStructure($structure, $course_uid);
            }
            // documentos
            $documents = $request->input('documents');
            $documents = json_decode($documents, true);
            $course_bd->updateDocuments($documents);

            return response()->json(['message' => ($isNew) ? 'Se ha añadido el curso correctamente' : 'Se ha actualizado el curso correctamente'], 200);
        }, 5);
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
        $query = $course->students()->with('courseStudentDocuments');

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

        return response()->json(['message' => 'Añadidos estudiantes al curso'], 200);
    }

    public function deleteCourseStudents(Request $request)
    {

        $uids = $request->input('uids');

        CoursesUsersModel::destroy($uids);

        return response()->json(['message' => 'Estudiantes del curso eliminados correctamente'], 200);
    }

    public function approveInscriptionsCourse(Request $request)
    {

        $selectedCourseStudents = $request->input('uids');

        CoursesStudentsModel::whereIn('uid', $selectedCourseStudents)->update(['approved' => 1]);

        return response()->json(['message' => 'Inscripciones aprobadas correctamente'], 200);
    }

    public function rejectInscriptionsCourse(Request $request)
    {
        $selectedCourseStudents = $request->input('uids');

        CoursesStudentsModel::whereIn('uid', $selectedCourseStudents)->update(['approved' => 0]);

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
                            'block_uid' => $blockData['uid'],
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
}
