<?php

namespace App\Http\Controllers\LearningObjects;

use App\Exceptions\OperationFailedException;
use App\Models\CallsModel;
use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Jobs\SendChangeStatusEducationalProgramNotification;
use App\Jobs\SendEducationalProgramNotificationToManagements;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramTagsModel;
use App\Models\CategoriesModel;
use App\Models\CourseStatusesModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EducationalsProgramsCategoriesModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\UsersModel;
use App\Models\CoursesTagsModel;
use App\Models\EducationalProgramsStudentsDocumentsModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use League\Csv\Reader;
use App\Models\EducationalProgramEmailContactsModel;
use App\Models\EducationalProgramsPaymentTermsModel;
use App\Rules\NifNie;
use App\Services\CertidigitalService;
use App\Services\KafkaService;
use Illuminate\Support\Facades\Auth;

class EducationalProgramsController extends BaseController
{
    protected $certidigitalService;

    public function __construct(CertidigitalService $certidigitalService)
    {
        $this->certidigitalService = $certidigitalService;
    }

    public function index()
    {

        $calls = CallsModel::all()->toArray();
        $educational_program_types = EducationalProgramTypesModel::all()->toArray();
        $categories = CategoriesModel::with('parentCategory')->get();

        $rolesUser = Auth::user()['roles']->pluck("code")->toArray();

        $variables_js = [
            "frontUrl" => env('FRONT_URL'),
            "rolesUser" => $rolesUser
        ];

        return view(
            'learning_objects.educational_programs.index',
            [
                "page_name" => "Listado de programas formativos",
                "page_title" => "Listado de programas formativos",
                "resources" => [
                    "resources/js/learning_objects_module/educational_programs.js"
                ],
                "tabulator" => true,
                "calls" => $calls,
                "educational_program_types" => $educational_program_types,
                "tomselect" => true,
                "categories" => $categories,
                "coloris" => true,
                "variables_js" => $variables_js,
                "submenuselected" => "learning-objects-educational-programs",
            ]
        );
    }

    public function getEducationalPrograms(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = EducationalProgramsModel::join("educational_program_types as educational_program_type", "educational_program_type.uid", "=", "educational_programs.educational_program_type_uid", "left")
            ->join("calls", "educational_programs.call_uid", "=", "calls.uid", "left")
            ->join("educational_program_statuses", "educational_programs.educational_program_status_uid", "=", "educational_program_statuses.uid", "left");

        // Si no es gestor, sólo puede ver los programas formativos que ha creado
        if (!auth()->user()->hasAnyRole(['MANAGEMENT'])) {
            $query->where('educational_programs.creator_user_uid', auth()->user()->uid);
        }

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('educational_programs.name', 'ILIKE', "%{$search}%")
                    ->orWhere('educational_programs.description', 'ILIKE', "%{$search}%")
                    ->orWhere('identifier', $search);
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $query->select("educational_programs.*", "educational_program_type.name as educational_program_type_name", "calls.name as call_name", 'educational_program_statuses.name as status_name', 'educational_program_statuses.code as status_code');
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

    public function sendCredentials(Request $request)
    {
        $educationalProgramUid = $request->input('educational_program_uid');
        $this->certidigitalService->emissionsCredentialEducationalProgram($educationalProgramUid);
        return response()->json(['message' => 'Se han enviado las credenciales correctamente'], 200);
    }

    // En función de la acción y del estado actual del curso, se establece el nuevo estado
    private function getStatusEducationalProgram($action, $educationalProgramBd)
    {
        $isUserManagement = Auth::user()->hasAnyRole(['MANAGEMENT']);

        $actualStatusEducationalProgram = $educationalProgramBd->status->code ?? null;

        $necessaryApprovalEditions = app('general_options')['necessary_approval_editions'];

        if ($isUserManagement || ($educationalProgramBd->educational_program_origin_uid && !$necessaryApprovalEditions)) {
            return $this->statusEducationalProgramUserManagementOrEdition($action, $actualStatusEducationalProgram);
        } else {
            return $this->statusEducationalProgramUserTeacher($action, $actualStatusEducationalProgram);
        }
    }

    private function statusEducationalProgramUserManagementOrEdition($action, $actualStatusEducationalProgram)
    {
        $statuses = EducationalProgramStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'ACCEPTED_PUBLICATION'
        ])->get()->keyBy('code');

        if ($action === "submit" && (!$actualStatusEducationalProgram || $actualStatusEducationalProgram === "INTRODUCTION")) {
            return $statuses['ACCEPTED_PUBLICATION'];
        } else if ($action === "draft" && (!$actualStatusEducationalProgram || $actualStatusEducationalProgram === "INTRODUCTION")) {
            return $statuses['INTRODUCTION'];
        } else return null;
    }

    private function statusEducationalProgramUserTeacher($action, $actualStatusCourse)
    {
        $statuses = EducationalProgramStatusesModel::whereIn('code', [
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

    /**
     * Crea una nueva convocatoria.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveEducationalProgram(Request $request)
    {
        adaptRequestDatesToUTC($request);

        $educational_program_uid = $request->input("educational_program_uid");

        if ($educational_program_uid) {
            $educational_program = EducationalProgramsModel::find($educational_program_uid);
            $isNew = false;
        } else {
            $educational_program = new EducationalProgramsModel();
            $educational_program_uid = generate_uuid();
            $educational_program->uid = $educational_program_uid;
            $educational_program->identifier = $this->generateIdentificerEducationalProgram();
            $educational_program->creator_user_uid = auth()->user()->uid;
            $isNew = true;
        }

        $errors = $this->validateEducationalProgram($request);

        if ($errors->any()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errors], 400);
        }

        if (!$isNew) {
            $this->validateStatusProgram($educational_program);
        }

        $this->validateCoursesAddedEducationalProgram($request, $educational_program);

        $action = $request->input('action');

        $newStatus = $this->getStatusEducationalProgram($action, $educational_program);

        DB::transaction(function () use ($request, &$isNew, $educational_program, $newStatus) {

            $isManagement = auth()->user()->hasAnyRole(["MANAGEMENT"]);

            if ($educational_program->educational_program_origin_uid && !$isManagement) {
                $this->fillEducationalProgramEdition($request, $educational_program);
            } else {
                $this->fillEducationalProgram($request, $educational_program);
            }

            $this->handleImageUpload($request, $educational_program);

            if ($newStatus) {
                $educational_program->educational_program_status_uid = $newStatus->uid;

                if ($newStatus->code === 'PENDING_APPROVAL') {
                    dispatch(new SendEducationalProgramNotificationToManagements($educational_program->toArray()));
                }
            }

            $educational_program->save();

            $this->handleEmails($request, $educational_program);

            $validateStudentsRegistrations = $request->input("validate_student_registrations");

            if ($validateStudentsRegistrations) {
                $this->syncDocuments($request, $educational_program);
            } else {
                $educational_program->deleteDocuments();
            }

            $paymentMode = $request->input('payment_mode');
            if ($paymentMode == "INSTALLMENT_PAYMENT") {
                $this->updatePaymentTerms($request, $educational_program);
            } else if ($paymentMode == "SINGLE_PAYMENT") {
                $educational_program->paymentTerms()->delete();
            }

            if ($newStatus && $newStatus->code === 'ACCEPTED_PUBLICATION') {
                $courses = $request->input('courses');
                $courses = CoursesModel::whereIn('uid', $courses)
                    ->has('lmsSystem')
                    ->with('lmsSystem')
                    ->get();

                $this->sendNotificationCoursesAcceptedPublicationToKafka($courses);
            }

            $this->certidigitalService->createUpdateEducationalProgramCredential($educational_program->uid);

            $this->logAction($isNew, $educational_program->name);
        });

        return response()->json(['message' => $isNew ? 'Programa formativo añadido correctamente' : 'Programa formativo actualizado correctamente']);
    }

    public function sendNotificationCoursesAcceptedPublicationToKafka($courses)
    {
        $kafkaService = new KafkaService();

        $courseToSend = [];

        foreach ($courses as $course) {
            $courseToSend[] = [
                'topic' => $course->lmsSystem->identifier,
                'key' => 'course_accepted_publication',
                'value' => [
                    'course_uid' => $course->uid,
                    'title' => $course->title,
                    "description" => $course->description,
                    'realization_start_date' => $course->realization_start_date,
                    'realization_finish_date' => $course->realization_start_date,
                ]
            ];
        }

        $kafkaService->sendMessages($courseToSend);
    }

    private function updatePaymentTerms($request, $educationalProgramBd)
    {
        $paymentTerms = $request->input('payment_terms');
        $paymentTerms = json_decode($paymentTerms, true);
        $this->syncPaymentTerms($paymentTerms, $educationalProgramBd);
    }

    private function syncPaymentTerms($paymentTerms, $educationalProgramBd)
    {
        $existingUids = $educationalProgramBd->paymentTerms()->pluck('uid')->toArray();

        $receivedUids = array_column($paymentTerms, 'uid');

        foreach ($paymentTerms as $paymentTerm) {
            if (in_array($paymentTerm['uid'], $existingUids)) {
                EducationalProgramsPaymentTermsModel::where('uid', $paymentTerm['uid'])->update([
                    'educational_program_uid' => $educationalProgramBd->uid,
                    'name' => $paymentTerm['name'],
                    'start_date' => $paymentTerm['start_date'],
                    'finish_date' => $paymentTerm['finish_date'],
                    'cost' => $paymentTerm['cost'],
                ]);
            } else {
                $educationalProgramBd->paymentTerms()->create([
                    'uid' => generate_uuid(),
                    'educational_program_uid' => $educationalProgramBd->uid,
                    'name' => $paymentTerm['name'],
                    'start_date' => $paymentTerm['start_date'],
                    'finish_date' => $paymentTerm['finish_date'],
                    'cost' => $paymentTerm['cost'],
                ]);
            }
        }

        $uidsToDelete = array_diff($existingUids, $receivedUids);
        if (!empty($uidsToDelete)) {
            EducationalProgramsModel::whereIn('uid', $uidsToDelete)->delete();
        }
    }

    function handleEmails($request, $educational_program)
    {
        $contact_emails = $request->input('contact_emails');
        $contact_emails = json_decode($contact_emails, true);

        if (!empty($contact_emails)) {
            $current_contact_emails = EducationalProgramEmailContactsModel::where('educational_program_uid', $educational_program->uid)->pluck('email')->toArray();

            $contact_emails_to_add = array_diff($contact_emails, $current_contact_emails);
            $contact_emails_to_delete = array_diff($current_contact_emails, $contact_emails);

            EducationalProgramEmailContactsModel::where('educational_program_uid', $educational_program->uid)->whereIn('email', $contact_emails_to_delete)->delete();

            $insertData = [];
            foreach ($contact_emails_to_add as $contact_email) {
                $insertData[] = [
                    'uid' => generate_uuid(),
                    'educational_program_uid' => $educational_program->uid,
                    'email' => $contact_email
                ];
            }

            EducationalProgramEmailContactsModel::insert($insertData);
        } else {
            EducationalProgramEmailContactsModel::where('educational_program_uid', $educational_program->uid)->delete();
        }
    }

    private function validateStatusProgram($educational_program)
    {
        $isUserManagement = auth()->user()->hasAnyRole(["MANAGEMENT"]);

        if ($isUserManagement) return;

        if (!in_array($educational_program->status->code, ["INTRODUCTION", "UNDER_CORRECTION_PUBLICATION", "UNDER_CORRECTION_APPROVAL"])) {
            throw new OperationFailedException('No puedes editar un programa formativo en este estado', 400);
        }
    }

    private function syncDocuments($request, $educational_program)
    {
        $documents = $request->input('documents');
        $documents = json_decode($documents, true);
        $educational_program->updateDocuments($documents);
    }

    private function syncItemsTags($request, $educational_program)
    {
        $current_tags = EducationalProgramTagsModel::where('educational_program_uid', $educational_program->uid)->pluck('tag')->toArray();

        $tags = $request->input('tags');
        $tags = json_decode($tags, true);

        // Identificar qué items son nuevos y cuáles deben ser eliminados
        $items_to_add = array_diff($tags, $current_tags);
        $items_to_delete = array_diff($current_tags, $tags);

        // Eliminar los items que ya no son necesarios
        EducationalProgramTagsModel::where('educational_program_uid', $educational_program->uid)->whereIn('tag', $items_to_delete)->delete();

        // Preparar el array para la inserción masiva de nuevos items
        $insertData = [];
        foreach ($items_to_add as $item) {
            $insertData[] = [
                'uid' => generate_uuid(),
                'educational_program_uid' => $educational_program->uid,
                'tag' => $item
            ];
        }

        // Insertar todos los nuevos items en una única operación de BD
        EducationalProgramTagsModel::insert($insertData);
    }

    private function syncCategories($request, $educationalProgram)
    {
        // Categorías
        $categories = $request->input('categories');
        $categories = json_decode($categories, true);
        $categories_bd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

        EducationalsProgramsCategoriesModel::where('educational_program_uid', $educationalProgram->uid)->delete();
        $categories_to_sync = [];

        foreach ($categories_bd as $category_uid) {
            $categories_to_sync[] = [
                'uid' => generate_uuid(),
                'educational_program_uid' => $educationalProgram->uid,
                'category_uid' => $category_uid
            ];
        }

        $educationalProgram->categories()->sync($categories_to_sync);
    }

    private function handleImageUpload($request, $educational_program)
    {
        if ($request->file('image_path')) {
            $file = $request->file('image_path');
            $path = 'images/educational-programs-images';

            $destinationPath = public_path($path);

            $filename = add_timestamp_name_file($file);

            $file->move($destinationPath, $filename);

            $educational_program->image_path = $path . "/" . $filename;
        }
    }

    private function fillEducationalProgramEdition($request, $educational_program)
    {
        $baseFields = [
            "min_required_students",
            "inscription_start_date",
            "inscription_finish_date",
            "validate_student_registrations",
            "cost",
            "realization_start_date",
            "realization_finish_date",
            "featured_slider",
            "featured_slider",
            "payment_mode"
        ];

        $conditionalFieldsGeneral = [
            'cost'
        ];

        $conditionalFieldsDates = [
            "enrolling_start_date",
            "enrolling_finish_date"
        ];

        $conditionalRestFields = [
            "evaluation_criteria"
        ];

        $validateStudentsRegistrations = $request->input("validate_student_registrations");
        $cost = $request->input("cost");

        $fields = $baseFields;

        if ($validateStudentsRegistrations) {
            $fields = array_merge($fields, $conditionalRestFields);
        }

        $paymentMode = $request->input('payment_mode');
        if (($cost && $cost > 0 && $paymentMode == 'SINGLE_PAYMENT') || $validateStudentsRegistrations) {
            $fields = array_merge($fields, $conditionalFieldsDates, $conditionalFieldsGeneral);
        }

        $educational_program->fill($request->only($fields));
        // Establecer a null los campos que no están en la lista de campos a actualizar
        $allFields = array_merge($fields, $conditionalFieldsDates, $conditionalRestFields);
        foreach ($allFields as $field) {
            if (!in_array($field, $fields)) {
                $educational_program->$field = null;
            }
        }
    }

    private function fillEducationalProgram($request, $educational_program)
    {
        $baseFields = [
            'name',
            'description',
            'educational_program_type_uid',
            'call_uid',
            'inscription_start_date',
            'inscription_finish_date',
            'min_required_students',
            'validate_student_registrations',
            'featured_slider',
            'featured_slider_title',
            'featured_slider_description',
            'featured_slider_color_font',
            'featured_main_carrousel',
            'realization_start_date',
            'realization_finish_date',
            'payment_mode'
        ];

        $conditionalFieldsGeneral = ['cost'];
        $conditionalFieldsDates = ['enrolling_start_date', 'enrolling_finish_date'];
        $conditionalFieldsEvaluationCriteria = ['evaluation_criteria'];

        $validateStudentsRegistrations = $request->input("validate_student_registrations");
        $cost = $request->input("cost");
        $paymentMode = $request->input('payment_mode');

        $fields = $baseFields;
        if ($validateStudentsRegistrations || ($cost && $cost > 0 && $paymentMode == 'SINGLE_PAYMENT')) {
            $fields = array_merge($fields, $conditionalFieldsDates, $conditionalFieldsGeneral);
        }

        if ($validateStudentsRegistrations) {
            $fields = array_merge($fields, $conditionalFieldsEvaluationCriteria);
        }

        $educational_program->fill($request->only($fields));

        if ($request->hasFile('featured_slider_image_path')) {
            $educational_program->featured_slider_image_path = saveFile($request->file('featured_slider_image_path'), 'images/carrousel-images', null, true);
        }

        // Ponemos a null los campos que no corresponden
        $allFields = array_merge($baseFields, $conditionalFieldsGeneral, $conditionalFieldsDates, $conditionalFieldsEvaluationCriteria);
        foreach (array_diff($allFields, $fields) as $field) {
            $educational_program->$field = null;
        }

        $educational_program->save();

        $this->handleCourses($request, $educational_program);
        $this->syncItemsTags($request, $educational_program);
        $this->syncCategories($request, $educational_program);
    }

    private function handleCourses($request, $educational_program)
    {
        $statusesCourses = CourseStatusesModel::whereIn('code', [
            'READY_ADD_EDUCATIONAL_PROGRAM',
            'ADDED_EDUCATIONAL_PROGRAM'
        ])
            ->get()
            ->keyBy('code');

        $coursesUidsToAdd = $request->input('courses');

        // Cursos que se quitan del programa formativo
        $coursesToRemoveEducationalProgram = $educational_program->courses->filter(function ($course) use ($coursesUidsToAdd) {
            return !in_array($course->uid, $coursesUidsToAdd);
        });

        $coursesUidsToRemoveEducationalProgram = $coursesToRemoveEducationalProgram->pluck('uid');

        CoursesModel::whereIn('uid', $coursesUidsToRemoveEducationalProgram)->update([
            'educational_program_uid' => null,
            'course_status_uid' => $statusesCourses["READY_ADD_EDUCATIONAL_PROGRAM"]->uid
        ]);

        CoursesModel::whereIn('uid', $coursesUidsToAdd)->update([
            'educational_program_uid' => $educational_program->uid,
            'course_status_uid' => $statusesCourses["ADDED_EDUCATIONAL_PROGRAM"]->uid
        ]);
    }

    private function logAction($isNew, $educationalProgramName)
    {
        $logMessage = $isNew ? 'Programa formativo añadido: ' : 'Programa formativo actualizado: ';
        $logMessage .= $educationalProgramName;
        LogsController::createLog($logMessage, 'Programas formativos', auth()->user()->uid);
    }

    private function getValidationMessages($enrollingDates)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'educational_program_type_uid.required' => 'El tipo de programa formativo es obligatorio',
            'inscription_start_date.required' => 'La fecha de inicio de inscripción es obligatoria',
            'inscription_finish_date.required' => 'La fecha de fin de inscripción es obligatoria',
            'inscription_start_date.after_or_equal' => 'La fecha de inicio de inscripción no puede ser anterior a la fecha y hora actual.',
            'inscription_finish_date.after_or_equal' => 'La fecha de fin de inscripción no puede ser anterior a la fecha de inicio de inscripción.',
            'enrolling_start_date.required' => 'La fecha de inicio de matriculación es obligatoria',
            'enrolling_finish_date.required' => 'La fecha de fin de matriculación es obligatoria',
            'enrolling_start_date.after_or_equal' => 'La fecha de inicio de matriculación no puede ser anterior a la fecha de fin de inscripción.',
            'enrolling_finish_date.after_or_equal' => 'La fecha de fin de matriculación no puede ser anterior a la fecha de inicio de matriculación.',
            'min_required_students.integer' => 'El número mínimo de estudiantes debe ser un número entero',
            'evaluation_criteria.required_if' => 'Los criterios de evaluación son obligatorios si se valida la inscripción de estudiantes',
            'realization_start_date.required' => 'La fecha de inicio de realización es obligatoria',
            'realization_finish_date.required' => 'La fecha de fin de realización es obligatoria',
            'realization_finish_date.after_or_equal' => 'La fecha de fin de realización no puede ser anterior a la fecha de inicio de realización',
            'courses.required' => 'Debes añadir algún curso',
            'call_uid.required' => 'La convocatoria es obligatoria',
            'featured_slider_title.required_if' => 'El título del slider es obligatorio si se activa el slider',
            'featured_slider_description.required_if' => 'La descripción del slider es obligatoria si se activa el slider',
            'featured_slider_image_path.required_if' => 'La imagen del slider es obligatoria si se activa el slider',
            'featured_slider_color_font.required_if' => 'El color de la fuente del slider es obligatorio si se activa el slider',
        ];

        if ($enrollingDates) {
            $messages["realization_start_date.after_or_equal"] = "La fecha de inicio de realización no puede ser anterior a la fecha de fin de matriculación";
        } else {
            $messages["realization_start_date.after_or_equal"] = "La fecha de inicio de realización no puede ser anterior a la fecha de fin de inscripción";
        }

        return $messages;
    }

    private function validateEducationalProgram($request)
    {
        $rules = [
            'name' => 'required|max:255',
            'educational_program_type_uid' => 'required',
            'inscription_start_date' => 'required|date',
            'inscription_finish_date' => 'required|date|after_or_equal:inscription_start_date',
            'min_required_students' => 'nullable|integer',
            'validate_student_registrations' => 'boolean',
            'evaluation_criteria' => 'required_if:validate_student_registrations,1',
            'courses' => 'required|array',
            'featured_slider_title' => 'required_if:featured_slider,1',
            'featured_slider_description' => 'required_if:featured_slider,1',
            'featured_slider_color_font' => 'required_if:featured_slider,1',
            'lms_system_uid' => 'required_with:lms_url',
            'featured_slider_image_path' => [
                function ($attribute, $value, $fail) use ($request) {
                    $featuredSliderImagePath = $request->input('featured_slider_image_path');
                    $educationalProgramUid = $request->input('educational_program_uid');

                    if ($featuredSliderImagePath && !$educationalProgramUid && !$value) {
                        $fail('Debes subir una imagen destacada para el slider');
                    }
                },
            ],
        ];

        if (app('general_options')['operation_by_calls']) {
            $rules['call_uid'] = 'required';
        }

        $paymentMode = $request->input('payment_mode');

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

        $validateStudentsRegistrations = $request->input("validate_student_registrations");
        $messages = $this->getValidationMessages($validateStudentsRegistrations);
        $this->addRulesDates($validateStudentsRegistrations, $rules);
        $validator = Validator::make($request->all(), $rules, $messages);

        $errorsValidator = $validator->errors();

        return $errorsValidator;
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

    /**
     * Añade las reglas de validación de fechas a las reglas de validación.
     * Si el curso tiene validación de estudiantes o un coste, se solicita plazo de matriculación.
     * Si no, se valida simplemente el plazo de realización
     */
    private function addRulesDates($validateStudentsRegistrations, &$rules)
    {
        // Si se valida la inscripción de estudiantes o el curso tiene un coste, se solicita plazo de matriculación
        if ($validateStudentsRegistrations) {
            $rules['enrolling_start_date'] = 'required|date|after_or_equal:inscription_finish_date';
            $rules['enrolling_finish_date'] = 'required|date|after_or_equal:enrolling_start_date';

            $rules['realization_start_date'] = 'required|date|after_or_equal:enrolling_finish_date';
            $rules['realization_finish_date'] = 'required|date|after_or_equal:realization_start_date';
        } else {
            $rules['realization_start_date'] = 'required|date|after_or_equal:inscription_finish_date';
            $rules['realization_finish_date'] = 'required|date|after_or_equal:realization_start_date';
        }
    }

    /**
     * Valida que los cursos añadidos a un programa formativo estén marcados que pertenecen
     * a un programa formativo y tienen el estado READY_ADD_EDUCATIONAL_PROGRAM
     */
    private function validateCoursesAddedEducationalProgram($request, $educational_program)
    {
        $courses = $request->input('courses');

        if (empty($courses)) return;

        $coursesBd = CoursesModel::whereIn('uid', $courses)->with('status')->get();

        // Validamos que los cursos pertenezcan a un programa formativo
        $coursesNotBelongingEducationalProgram = $coursesBd->filter(function ($course) {
            return !$course->belongs_to_educational_program;
        });

        if ($coursesNotBelongingEducationalProgram->count()) {
            throw new OperationFailedException(
                'Algún curso no pertenece a un programa formativo',
                400
            );
        }

        // Validamos las fechas de realización de los cursos.
        // Los cursos deben tener un período de realización que esté entre las fechas de realización del programa formativo
        $realization_start_date = $request->input('realization_start_date');
        $realization_finish_date = $request->input('realization_finish_date');

        $coursesNotBetweenRealizationDate = $coursesBd->filter(function ($course) use ($realization_start_date, $realization_finish_date) {
            if ($course->realization_start_date && $course->realization_finish_date) {
                return $course->realization_start_date < $realization_start_date || $course->realization_finish_date > $realization_finish_date;
            }
        });

        if ($coursesNotBetweenRealizationDate->count()) {
            throw new OperationFailedException(
                'Algunos cursos no están entre las fechas de realización del programa formativo',
                400
            );
        }

        $coursesBelongingOtherEducationalPrograms = $coursesBd->filter(function ($course) use ($educational_program) {
            return $course->educational_program_uid && $course->educational_program_uid !== $educational_program->uid;
        });

        if ($coursesBelongingOtherEducationalPrograms->count()) {
            throw new OperationFailedException(
                'Algunos cursos pertenecen a otro programa formativo',
                400
            );
        }

        $newCourses = $coursesBd->filter(function ($course) use ($educational_program) {
            return $course->educational_program_uid !== $educational_program->uid;
        });

        $coursesNotHavingStatusReadyAddEducationalProgram = $newCourses->filter(function ($course) {
            return $course->status->code !== 'READY_ADD_EDUCATIONAL_PROGRAM';
        });

        if ($coursesNotHavingStatusReadyAddEducationalProgram->count()) {
            throw new OperationFailedException(
                'Algunos cursos no tienen el estado correcto para ser añadidos a un programa formativo',
                400
            );
        }
    }

    /**
     * Elimina una convocatoria específica.
     *
     * @param  string $call_uid El UID de la convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteEducationalPrograms(Request $request)
    {
        $uids = $request->input('uids');

        $educationalPrograms = EducationalProgramsModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($educationalPrograms) {
            foreach ($educationalPrograms as $educationalProgram) {
                $educationalProgram->delete();
                LogsController::createLog("Eliminación de programa formativo: " . $educationalProgram->name, 'Programas formativos', auth()->user()->uid);
            }
        });

        return response()->json(['message' => 'Programas formativos eliminados correctamente']);
    }

    /**
     * Obtiene un programa formativo por uid
     */

    public function getEducationalProgram($educational_program_uid)
    {
        // if (!$educational_program_uid) {
        //     return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        // }

        $educational_program = EducationalProgramsModel::where('uid', $educational_program_uid)->with(['courses', 'status', 'tags', 'categories', 'EducationalProgramDocuments', 'contact_emails', 'paymentTerms'])->first();

        if (!$educational_program) {
            return response()->json(['message' => 'El programa formativo no existe'], 406);
        }

        $dates = [
            'inscription_start_date',
            'inscription_finish_date',
            'realization_start_date',
            'realization_finish_date',
            'enrolling_start_date',
            'enrolling_finish_date'
        ];

        adaptDatesModel($educational_program, $dates, false);

        return response()->json($educational_program, 200);
    }

    public function searchCoursesWithoutEducationalProgram($search)
    {
        $courses_query = CoursesModel::with('status')->where('belongs_to_educational_program', true)
            ->whereHas('status', function ($query) {
                $query->where('code', 'READY_ADD_EDUCATIONAL_PROGRAM');
            });

        if ($search) {
            $courses_query->where(function ($query) use ($search) {
                $query->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $courses = $courses_query->get();

        return response()->json($courses, 200);
    }

    /**
     * Cambia el estado a un array de programas formativos
     */
    public function changeStatusesEducationalPrograms(Request $request)
    {

        if (!auth()->user()->hasAnyRole(["ADMINISTRATOR", "MANAGEMENT"])) {
            throw new OperationFailedException('No tienes permisos para realizar esta acción', 403);
        }

        $changesEducationalProgramsStatuses = $request->input('changesEducationalProgramsStatuses');

        if (!$changesEducationalProgramsStatuses) {
            return response()->json(['message' => 'No se han enviado los datos correctamente'], 406);
        }

        // Obtenemos los cursos de la base de datos
        $educational_programs_bd = EducationalProgramsModel::whereIn('uid', array_column($changesEducationalProgramsStatuses, "uid"))->with(['status', 'creatorUser'])->get()->keyBy("uid");

        // Excluímos los estados a los que no se pueden cambiar manualmente.
        $statuses_educational_programs = EducationalProgramStatusesModel::whereNotIn(
            'code',
            [
                'INSCRIPTION',
                'DEVELOPMENT',
                'PENDING_INSCRIPTION',
                'FINISHED'
            ]
        )
            ->get()
            ->keyBy('code');

        DB::transaction(function () use ($changesEducationalProgramsStatuses, $educational_programs_bd, $statuses_educational_programs) {
            // Recorremos los cursos que nos vienen en el request y los comparamos con los de la base de datos
            foreach ($changesEducationalProgramsStatuses as $changeEducationalProgramStatus) {
                $educational_program = $educational_programs_bd[$changeEducationalProgramStatus['uid']];
                $this->updateStatusEducationalProgram($changeEducationalProgramStatus, $educational_program, $statuses_educational_programs);

                // Enviamos notificación al creador del programa
                dispatch(new SendChangeStatusEducationalProgramNotification($educational_program->toArray()));
            }
        });

        return response()->json(['message' => 'Se han actualizado los estados de los programas formativos correctamente'], 200);
    }

    private function updateStatusEducationalProgram($changeEducationalProgramStatus, $educational_program_bd, $statuses_educational_programs)
    {
        $status_bd = $statuses_educational_programs[$changeEducationalProgramStatus['status']];

        $educational_program_bd->educational_program_status_uid = $status_bd->uid;
        $educational_program_bd->status_reason = $changeEducationalProgramStatus['reason'] ?? null;

        $educational_program_bd->save();

        if ($changeEducationalProgramStatus['status'] == 'ACCEPTED_PUBLICATION') {
            $this->sendNotificationCoursesAcceptedPublicationToKafka($educational_program_bd->courses);
        }
    }

    public function getEducationalProgramStudents(Request $request, $educational_program_uid)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $educational_program = EducationalProgramsModel::where('uid', $educational_program_uid)->first();

        $query = $educational_program->students()->with(
            [
                'educationalProgramDocuments' => function ($query) use ($educational_program_uid) {
                    $query->whereHas('educationalProgramDocument', function ($query) use ($educational_program_uid) {
                        $query->where('educational_program_uid', $educational_program_uid);
                    });
                },
                'educationalProgramDocuments.educationalProgramDocument'
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
                if ($order['field'] == 'acceptance_status') {
                    $query->join('educational_programs_students', function ($join) use ($educational_program_uid) {
                        $join->on('users.uid', '=', 'educational_programs_students.user_uid')
                            ->where('educational_programs_students.educational_program_uid', '=', $educational_program_uid);
                    })->orderBy('educational_programs_students.acceptance_status', $order['dir']);
                } else {
                    $query->orderBy($order['field'], $order['dir']);
                }
            }
        }

        // Aplicar paginación
        $students = $query->paginate($size);

        return response()->json($students, 200);
    }

    public function enrollStudents(Request $request)
    {

        $users = $request->get('usersToEnroll');

        if (!$users || !count($users)) {
            throw new OperationFailedException('No se han seleccionado alumnos');
        }

        $usersenrolled = false;

        foreach ($users as $user) {

            $existingEnrollment = EducationalProgramsStudentsModel::where('educational_program_uid', $request->get('EducationalProgramUid'))
                ->where('user_uid', $user)
                ->first();

            if ($existingEnrollment) {
                $usersenrolled = true;
                continue;
            }

            $enroll = new EducationalProgramsStudentsModel();
            $enroll->uid = generate_uuid();
            $enroll->educational_program_uid = $request->get('EducationalProgramUid');
            $enroll->user_uid = $user;
            $enroll->acceptance_status = 'PENDING';
            $messageLog = "Alumno añadido a programa formativo";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Programas formativos', auth()->user()->uid);
            });
        }

        $message = "Alumnos añadidos al programa formativo";

        if ($usersenrolled == true) {
            $message = "Alumnos añadidos al programa formativo. Los ya registrados no se han añadido.";
        }

        return response()->json(['message' => $message], 200);
    }

    public function changeStatusInscriptionsEducationalProgram(Request $request)
    {

        $selectedEducationalProgramStudents = $request->input('uids');
        $status = $request->input('status');

        $educationalProgramsStudents = EducationalProgramsStudentsModel::whereIn('uid', $selectedEducationalProgramStudents)
            ->with('educationalProgram')
            ->get();

        DB::transaction(function () use ($educationalProgramsStudents, $status) {

            foreach ($educationalProgramsStudents as $courseStudent) {
                $courseStudent->acceptance_status = $status;
                $courseStudent->save();

                $this->saveGeneralNotificationAutomatic($courseStudent->educationalProgram, $status, $courseStudent->user_uid);
                $this->saveEmailNotificationAutomatic($courseStudent->educationalProgram, $status, $courseStudent->user_uid);
            }

            LogsController::createLog('Cambio de estado de inscripciones de programa formativo', 'Programas formativos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Estados de inscripciones cambiados correctamente'], 200);
    }

    private function saveGeneralNotificationAutomatic($educationalProgram, $status, $userUid)
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;

        if ($status == "ACCEPTED") {
            $generalNotificationAutomatic->title = "Inscripción a programa formativo aceptada";
            $generalNotificationAutomatic->description = "Tu inscripción en el programa formativo " . $educationalProgram->name . " ha sido aceptada";
        } else {
            $generalNotificationAutomatic->title = "Inscripción a programa formativo rechazada";
            $generalNotificationAutomatic->description = "Tu inscripción en el programa formativo " . $educationalProgram->name . " ha sido rechazada";
        }

        $generalNotificationAutomatic->entity = "educational_program";
        $generalNotificationAutomatic->entity_uid = $educationalProgram->uid;
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS')->first();
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
        $generalNotificationAutomaticUser->uid = generate_uuid();
        $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomaticUser->user_uid = $userUid;
        $generalNotificationAutomaticUser->save();
    }

    private function saveEmailNotificationAutomatic($educationalProgram, $status, $userUid)
    {
        $emailNotificationAutomatic = new EmailNotificationsAutomaticModel();
        $emailNotificationAutomatic->uid = generate_uuid();

        $emailParameters = [
            'educational_program_title' => $educationalProgram->title,
        ];

        if ($status == "ACCEPTED") {
            $emailNotificationAutomatic->subject = "Inscripción a programa formativo aceptada";
            $emailParameters["status"] = "ACCEPTED";
        } else {
            $emailNotificationAutomatic->subject = "Inscripción a programa formativo rechazada";
            $emailParameters["status"] = "REJECTED";
        }

        $emailNotificationAutomatic->template = "educational_program_inscription_status";
        $emailNotificationAutomatic->parameters = json_encode($emailParameters);
        $emailNotificationAutomatic->user_uid = $userUid;

        $emailNotificationAutomatic->save();
    }

    public function enrollStudentsCsv(Request $request)
    {

        $file = $request->file('attachment');
        $educational_program_uid = $request->get('educational_program_uid');

        $reader = Reader::createFromPath($file->path());

        foreach ($reader as $key => $row) {

            if ($key > 0) {
                $existingUser = UsersModel::where('email', $row[3])
                    ->first();

                if ($existingUser) {
                    $this->enrollUserCsv($existingUser->uid, $educational_program_uid);
                } else {
                    $this->validateStudentCsv($row, $key);
                    $this->singUpUser($row, $educational_program_uid);
                }
            }
        }

        $message = "Alumnos añadidos al programa formativo. Los ya registrados no se han añadido.";

        return response()->json(['message' => $message], 200);
    }

    private function validateStudentCsv($user, $index)
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

    public function enrollUserCsv($user_uid, $educational_program_uid)
    {

        $existingEnrollment = EducationalProgramsStudentsModel::where('educational_program_uid', $educational_program_uid)
            ->where('user_uid', $user_uid)
            ->first();

        if (!$existingEnrollment) {

            $enroll = new EducationalProgramsStudentsModel();
            $enroll->uid = generate_uuid();
            $enroll->educational_program_uid = $educational_program_uid;
            $enroll->user_uid = $user_uid;
            $enroll->acceptance_status = 'PENDING';
            $messageLog = "Alumno añadido a programa formativo";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Programas formativos', auth()->user()->uid);
            });
        }
    }

    public function singUpUser($row, $educational_program_uid)
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
            LogsController::createLog($messageLog, 'Programa formativo', auth()->user()->uid);
        });

        $this->enrollUserCsv($newUserUid, $educational_program_uid);
    }

    public function editionOrDuplicateEducationalProgram(Request $request)
    {

        $educational_program_uid = $request->input("educationalProgramUid");
        $action = $request->input('action');

        if (!in_array($action, ["edition", "duplication"])) throw new OperationFailedException('Acción no permitida', 400);

        $educational_program_bd = EducationalProgramsModel::where('uid', $educational_program_uid)->with(['tags', 'categories', 'EducationalProgramDocuments'])->first();

        if (!$educational_program_bd) return response()->json(['message' => 'El programa formativo no existe'], 406);

        $new_educational_program = $educational_program_bd->replicate();

        $concatName = $action === "edition" ? "(nueva edición)" : "(copia)";
        $new_educational_program->name = $new_educational_program->name . " " . $concatName;

        if ($action == "edition") {
            $new_educational_program->educational_program_origin_uid = $educational_program_uid;
        } else {
            $new_educational_program->educational_program_origin_uid = null;
        }

        $introduction_status = EducationalProgramStatusesModel::where('code', 'INTRODUCTION')->first();
        $new_educational_program->educational_program_status_uid = $introduction_status->uid;

        DB::transaction(function () use ($new_educational_program, $educational_program_bd, $educational_program_uid, $action) {
            $new_educational_program_uid = generate_uuid();
            $new_educational_program->uid = $new_educational_program_uid;
            $new_educational_program->identifier = $this->generateIdentificerEducationalProgram();
            $new_educational_program->save();

            $this->duplicateEducationalProgramDocuments($educational_program_bd, $new_educational_program_uid);

            $courses = CoursesModel::where('educational_program_uid', $educational_program_uid)->get();

            foreach ($courses as $course) {

                $this->duplicateCourse($course->uid, $new_educational_program_uid);
            }

            $this->duplicateEducationalProgramTags($educational_program_bd, $new_educational_program_uid);

            $this->duplicateEducationalProgramsCategories($educational_program_bd, $new_educational_program_uid, $new_educational_program);

            if ($educational_program_bd->payment_mode == "INSTALLMENT_PAYMENT") {
                $this->duplicateEducationalProgramsPaymentTerms($educational_program_bd, $new_educational_program_uid);
            }

            if ($action === "edition") $logMessage = 'Creación de edición de programa formativo';
            else $logMessage = 'Duplicación de programa formativo';

            LogsController::createLog($logMessage, 'Programas formativos', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => $action == 'edition' ? 'Edición creada correctamente' : 'Programa duplicado correctamente'], 200);
    }

    public function deleteInscriptionsEducationalProgram(Request $request)
    {
        $uids = $request->input('uids');

        EducationalProgramsStudentsModel::destroy($uids);

        return response()->json(['message' => 'Inscripciones eliminadas correctamente'], 200);
    }

    private function duplicateCourse($course_uid, $new_educational_program_uid)
    {
        $course_bd = CoursesModel::where('uid', $course_uid)->with(['teachers', 'tags', 'categories'])->first();

        if (!$course_bd) return response()->json(['message' => 'El curso no existe'], 406);

        $new_course = $course_bd->replicate();
        $new_course->title = $new_course->title;
        $new_course->identifier = $this->generateIdentificerEducationalProgram();
        $new_course->educational_program_uid = $new_educational_program_uid;
        $new_course->creator_user_uid = auth()->user()->uid;

        $introduction_status = CourseStatusesModel::where('code', 'ADDED_EDUCATIONAL_PROGRAM')->first();
        $new_course->course_status_uid = $introduction_status->uid;
        $new_course->belongs_to_educational_program = true;

        $new_course_uid = generate_uuid();
        $new_course->uid = $new_course_uid;

        $new_course->save();

        $this->duplicateCourseTeachers($course_bd, $new_course_uid, $new_course);

        $this->duplicateCourseTags($course_bd, $new_course_uid);

        $this->duplicateCourseCategories($course_bd, $new_course_uid, $new_course);
    }


    public function calculateMedianEnrollingsCategories(Request $request)
    {
        $categoriesUids = $request->input("categories_uids");
        $median = $this->getMedianInscribedCategories($categoriesUids);
        return response()->json(['median' => $median], 200);
    }

    private function getMedianInscribedCategories($categoriesUids)
    {
        $courses = EducationalProgramsModel::withCount([
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

        $median = calculateMedian($studentCounts->toArray());

        return $median;
    }

    private function duplicateEducationalProgramTags($educational_program_bd, $new_educational_program_uid)
    {
        $tags = $educational_program_bd->tags->pluck('tag')->toArray();
        $tags_to_add = [];
        foreach ($tags as $tag) {
            $tags_to_add[] = [
                'uid' => generate_uuid(),
                'educational_program_uid' => $new_educational_program_uid,
                'tag' => $tag,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        EducationalProgramTagsModel::insert($tags_to_add);
    }

    private function duplicateEducationalProgramsPaymentTerms($educational_program_bd, $new_educational_program_uid)
    {
        $paymentTerms = $educational_program_bd->paymentTerms->toArray();
        $paymentTerms_to_add = [];
        foreach ($paymentTerms as $paymentTerm) {
            $paymentTerms_to_add[] = [
                'uid' => generate_uuid(),
                'educational_program_uid' => $new_educational_program_uid,
                'name' => $paymentTerm['name'],
                'start_date' => $paymentTerm['start_date'],
                'finish_date' => $paymentTerm['finish_date'],
                'cost' => $paymentTerm['cost'],
            ];
        }
        EducationalProgramsPaymentTermsModel::insert($paymentTerms_to_add);
    }

    private function duplicateEducationalProgramsCategories($educational_program_bd, $new_educational_program_uid, $new_educational_program)
    {
        $categories = $educational_program_bd->categories->pluck('uid')->toArray();
        $categories_to_sync = [];
        foreach ($categories as $category_uid) {
            $categories_to_sync[] = [
                'uid' => generate_uuid(),
                'educational_program_uid' => $new_educational_program_uid,
                'category_uid' => $category_uid
            ];
        }
        $new_educational_program->categories()->sync($categories_to_sync);
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

    private function duplicateEducationalProgramDocuments($educational_program_bd, $new_educational_program_uid)
    {
        foreach ($educational_program_bd->EducationalProgramDocuments as $document) {
            $newEducationalProgramDocument = $document->replicate();
            $newEducationalProgramDocument->uid = generate_uuid();
            $newEducationalProgramDocument->educational_program_uid = $new_educational_program_uid;
            $newEducationalProgramDocument->save();
        }
    }

    public function downloadDocumentStudent(Request $request)
    {
        $uidDocument = $request->get('uidDocument');
        $document = EducationalProgramsStudentsDocumentsModel::where('uid', $uidDocument)->first();
        return response()->download(storage_path($document->document_path));
    }

    private function generateIdentificerEducationalProgram()
    {
        $educationalProgramsCount = EducationalProgramsModel::count();
        $identifier = 'PF-' . str_pad($educationalProgramsCount + 1, 4, '0', STR_PAD_LEFT);

        return $identifier;
    }
}
