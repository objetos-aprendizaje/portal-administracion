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
        $educationalProgramTypes = EducationalProgramTypesModel::all()->toArray();
        $categories = CategoriesModel::with('parentCategory')->get();

        $rolesUser = Auth::user()['roles']->pluck("code")->toArray();

        $variablesJs = [
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
                "educational_program_types" => $educationalProgramTypes,
                "tomselect" => true,
                "categories" => $categories,
                "coloris" => true,
                "variables_js" => $variablesJs,
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

    public function emitAllCredentials(Request $request)
    {
        $educationalProgramUid = $request->input('educational_program_uid');

        $educationalProgram = EducationalProgramsModel::where('uid', $educationalProgramUid)->with(["educationalProgramType", "students"])->first();

        // Comprobación de si el usuario tiene permiso para emitir credenciales para este curso
        $this->checkPermissionsEmitCredentials($educationalProgram->educationalProgramType);

        $studentsUids = $educationalProgram->students->pluck('uid')->toArray();

        $this->certidigitalService->emissionsCredentialEducationalProgram($educationalProgramUid, $studentsUids);

        return response()->json(['message' => 'Credenciales emitidas correctamente'], 200);
    }

    public function emitCredentials(Request $request)
    {
        $educationalProgramUid = $request->input('educational_program_uid');
        $studentsUids = $request->input('students_uids');

        $educationalProgram = EducationalProgramsModel::where('uid', $educationalProgramUid)->with("educationalProgramType")->first();

        // Comprobación de si el usuario tiene permiso para emitir credenciales para este curso
        $this->checkPermissionsEmitCredentials($educationalProgram->educationalProgramType);

        // Comprobación de si alguno de los alumnos ya tiene las credenciales emitidas
        $this->checkCredentialsStudentsEmissionsInEducationalProgram($educationalProgramUid, $studentsUids);

        $this->certidigitalService->emissionsCredentialEducationalProgram($educationalProgramUid, $studentsUids);

        return response()->json(['message' => 'Credenciales emitidas correctamente'], 200);
    }

    public function sendCredentials(Request $request)
    {
        $educationalProgramUid = $request->input('educational_program_uid');
        $studentsUids = $request->input('students_uids');

        $this->certidigitalService->sendCredentialsEducationalPrograms([$educationalProgramUid], $studentsUids);

        return response()->json(['message' => 'Se han enviado las credenciales correctamente'], 200);
    }

    public function sealCredentials(Request $request)
    {
        $educationalProgramUid = $request->input('educational_program_uid');
        $studentsUids = $request->input('students_uids');

        $this->certidigitalService->sealEducationalProgramsCredentials([$educationalProgramUid], $studentsUids);

        return response()->json(['message' => 'Credenciales selladas correctamente'], 200);
    }

    private function checkPermissionsEmitCredentials($educationalProgramType)
    {
        $userRoles = auth()->user()->roles()->get()->pluck('code')->toArray();

        if ($educationalProgramType->managers_can_emit_credentials && in_array('MANAGEMENT', $userRoles)) {
            return;
        } elseif ($educationalProgramType->teachers_can_emit_credentials && in_array('TEACHER', $userRoles)) {
            return;
        }

        throw new OperationFailedException('No tienes permisos para emitir credenciales en este curso', 422);
    }

    private function checkCredentialsStudentsEmissionsInEducationalProgram($educationalProgramUid, $studentsUids)
    {
        $educationalProgramsStudentsWithEmissions = EducationalProgramsStudentsModel::where('educational_program_uid', $educationalProgramUid)
            ->whereIn('user_uid', $studentsUids)
            ->where('emissions_block_uuid', "!=", null)
            ->exists();

        if ($educationalProgramsStudentsWithEmissions) {
            throw new OperationFailedException('No se pueden emitir credenciales porque alguno de los alumnos ya tiene credenciales emitidas', 422);
        }
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
        } elseif ($action === "draft" && (!$actualStatusEducationalProgram || $actualStatusEducationalProgram === "INTRODUCTION")) {
            return $statuses['INTRODUCTION'];
        } else {
            return null;
        }
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

    /**
     * Crea una nueva convocatoria.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveEducationalProgram(Request $request)
    {
        adaptRequestDatesToUTC($request);

        $educationalProgramUid = $request->input("educational_program_uid");

        if ($educationalProgramUid) {
            $educationalProgram = EducationalProgramsModel::find($educationalProgramUid);
            $isNew = false;
        } else {
            $educationalProgram = new EducationalProgramsModel();
            $educationalProgramUid = generateUuid();
            $educationalProgram->uid = $educationalProgramUid;
            $educationalProgram->identifier = $this->generateIdentificerEducationalProgram();
            $educationalProgram->creator_user_uid = auth()->user()->uid;
            $isNew = true;
        }

        $errors = $this->validateEducationalProgram($request);

        if ($errors->any()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errors], 400);
        }

        if (!$isNew) {
            $this->validateStatusProgram($educationalProgram);
        }

        $this->validateCoursesAddedEducationalProgram($request, $educationalProgram);

        $action = $request->input('action');

        $newStatus = $this->getStatusEducationalProgram($action, $educationalProgram);

        DB::transaction(function () use ($request, &$isNew, $educationalProgram, $newStatus) {

            // Copia para detectar cambios en el programa formativo
            $educationalProgramCopy = clone $educationalProgram;

            $isManagement = auth()->user()->hasAnyRole(["MANAGEMENT"]);

            if ($educationalProgram->educational_program_origin_uid && !$isManagement) {
                $this->fillEducationalProgramEdition($request, $educationalProgram);
            } else {
                $this->fillEducationalProgram($request, $educationalProgram);
            }

            $this->handleImageUpload($request, $educationalProgram);

            if ($newStatus) {
                $educationalProgram->educational_program_status_uid = $newStatus->uid;

                if ($newStatus->code === 'PENDING_APPROVAL') {
                    dispatch(new SendEducationalProgramNotificationToManagements($educationalProgram->toArray()));
                }
            }

            $educationalProgram->save();

            $this->handleEmails($request, $educationalProgram);

            $validateStudentsRegistrations = $request->input("validate_student_registrations");

            if ($validateStudentsRegistrations) {
                $this->syncDocuments($request, $educationalProgram);
            } else {
                $educationalProgram->deleteDocuments();
            }

            $paymentMode = $request->input('payment_mode');
            if ($paymentMode == "INSTALLMENT_PAYMENT") {
                $this->updatePaymentTerms($request, $educationalProgram);
            } elseif ($paymentMode == "SINGLE_PAYMENT") {
                $educationalProgram->paymentTerms()->delete();
            }

            if ($newStatus && $newStatus->code === 'ACCEPTED_PUBLICATION') {
                $courses = $request->input('courses');
                $courses = CoursesModel::whereIn('uid', $courses)
                    ->has('lmsSystem')
                    ->with('lmsSystem')
                    ->get();

                $this->sendNotificationCoursesAcceptedPublicationToKafka($courses);
            }

            $changesEducationalProgram = $this->detectChangesCredential($educationalProgram, $educationalProgramCopy);
            if($changesEducationalProgram || !$educationalProgram->certidigitalCredential) {
                $this->certidigitalService->createUpdateEducationalProgramCredential($educationalProgram->uid);
            }

            $this->logAction($isNew, $educationalProgram->name);
        });

        return response()->json(['message' => $isNew ? 'Programa formativo añadido correctamente' : 'Programa formativo actualizado correctamente']);
    }

    private function detectChangesCredential($educationalProgramAfterChanges, $educationalProgramBeforeChanges)
    {
        if ($educationalProgramAfterChanges->name != $educationalProgramBeforeChanges->name) {
            return true;
        }
        return false;
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
                    'uid' => generateUuid(),
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

    private function handleEmails($request, $educationalProgram)
    {
        $contactEmails = $request->input('contact_emails');
        $contactEmails = json_decode($contactEmails, true);

        if (!empty($contactEmails)) {
            $currentContactEmails = EducationalProgramEmailContactsModel::where('educational_program_uid', $educationalProgram->uid)->pluck('email')->toArray();

            $contactEmailsToAdd = array_diff($contactEmails, $currentContactEmails);
            $contactEmailsToDelete = array_diff($currentContactEmails, $contactEmails);

            EducationalProgramEmailContactsModel::where('educational_program_uid', $educationalProgram->uid)->whereIn('email', $contactEmailsToDelete)->delete();

            $insertData = [];
            foreach ($contactEmailsToAdd as $contactEmail) {
                $insertData[] = [
                    'uid' => generateUuid(),
                    'educational_program_uid' => $educationalProgram->uid,
                    'email' => $contactEmail
                ];
            }

            EducationalProgramEmailContactsModel::insert($insertData);
        } else {
            EducationalProgramEmailContactsModel::where('educational_program_uid', $educationalProgram->uid)->delete();
        }
    }

    private function validateStatusProgram($educationalProgram)
    {
        $isUserManagement = auth()->user()->hasAnyRole(["MANAGEMENT"]);

        if ($isUserManagement) {
            return;
        }

        if (!in_array($educationalProgram->status->code, ["INTRODUCTION", "UNDER_CORRECTION_PUBLICATION", "UNDER_CORRECTION_APPROVAL"])) {
            throw new OperationFailedException('No puedes editar un programa formativo en este estado', 400);
        }
    }

    private function syncDocuments($request, $educationalProgram)
    {
        $documents = $request->input('documents');
        $documents = json_decode($documents, true);
        $educationalProgram->updateDocuments($documents);
    }

    private function syncItemsTags($request, $educationalProgram)
    {
        $currentTags = EducationalProgramTagsModel::where('educational_program_uid', $educationalProgram->uid)->pluck('tag')->toArray();

        $tags = $request->input('tags');
        $tags = json_decode($tags, true);

        // Identificar qué items son nuevos y cuáles deben ser eliminados
        $itemsToAdd = array_diff($tags, $currentTags);
        $itemsToDelete = array_diff($currentTags, $tags);

        // Eliminar los items que ya no son necesarios
        EducationalProgramTagsModel::where('educational_program_uid', $educationalProgram->uid)->whereIn('tag', $itemsToDelete)->delete();

        // Preparar el array para la inserción masiva de nuevos items
        $insertData = [];
        foreach ($itemsToAdd as $item) {
            $insertData[] = [
                'uid' => generateUuid(),
                'educational_program_uid' => $educationalProgram->uid,
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
        $categoriesBd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

        EducationalsProgramsCategoriesModel::where('educational_program_uid', $educationalProgram->uid)->delete();
        $categoriesToSync = [];

        foreach ($categoriesBd as $categoryUid) {
            $categoriesToSync[] = [
                'uid' => generateUuid(),
                'educational_program_uid' => $educationalProgram->uid,
                'category_uid' => $categoryUid
            ];
        }

        $educationalProgram->categories()->sync($categoriesToSync);
    }

    private function handleImageUpload($request, $educationalProgram)
    {
        if ($request->file('image_path')) {
            $file = $request->file('image_path');
            $path = 'images/educational-programs-images';

            $destinationPath = public_path($path);

            $filename = addTimestampNameFile($file);

            $file->move($destinationPath, $filename);

            $educationalProgram->image_path = $path . "/" . $filename;
        }
    }

    private function fillEducationalProgramEdition($request, $educationalProgram)
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

        $educationalProgram->fill($request->only($fields));
        // Establecer a null los campos que no están en la lista de campos a actualizar
        $allFields = array_merge($fields, $conditionalFieldsDates, $conditionalRestFields);
        foreach ($allFields as $field) {
            if (!in_array($field, $fields)) {
                $educationalProgram->$field = null;
            }
        }
    }

    private function fillEducationalProgram($request, $educationalProgram)
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

        $educationalProgram->fill($request->only($fields));

        if ($request->hasFile('featured_slider_image_path')) {
            $educationalProgram->featured_slider_image_path = saveFile($request->file('featured_slider_image_path'), 'images/carrousel-images', null, true);
        }

        // Ponemos a null los campos que no corresponden
        $allFields = array_merge($baseFields, $conditionalFieldsGeneral, $conditionalFieldsDates, $conditionalFieldsEvaluationCriteria);
        foreach (array_diff($allFields, $fields) as $field) {
            $educationalProgram->$field = null;
        }

        $educationalProgram->save();

        $this->handleCourses($request, $educationalProgram);
        $this->syncItemsTags($request, $educationalProgram);
        $this->syncCategories($request, $educationalProgram);
    }

    private function handleCourses($request, $educationalProgram)
    {
        $statusesCourses = CourseStatusesModel::whereIn('code', [
            'READY_ADD_EDUCATIONAL_PROGRAM',
            'ADDED_EDUCATIONAL_PROGRAM'
        ])
            ->get()
            ->keyBy('code');

        $coursesUidsToAdd = $request->input('courses');

        // Cursos que se quitan del programa formativo
        $coursesToRemoveEducationalProgram = $educationalProgram->courses->filter(function ($course) use ($coursesUidsToAdd) {
            return !in_array($course->uid, $coursesUidsToAdd);
        });

        $coursesUidsToRemoveEducationalProgram = $coursesToRemoveEducationalProgram->pluck('uid');

        CoursesModel::whereIn('uid', $coursesUidsToRemoveEducationalProgram)->update([
            'educational_program_uid' => null,
            'course_status_uid' => $statusesCourses["READY_ADD_EDUCATIONAL_PROGRAM"]->uid
        ]);

        CoursesModel::whereIn('uid', $coursesUidsToAdd)->update([
            'educational_program_uid' => $educationalProgram->uid,
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
                    if ($validation !== true) {
                        $fail($validation);
                    }
                },
            ];
        }

        $validateStudentsRegistrations = $request->input("validate_student_registrations");
        $messages = $this->getValidationMessages($validateStudentsRegistrations);
        $this->addRulesDates($validateStudentsRegistrations, $rules);
        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator->errors();
    }

    // Validación del bloque de plazos de pago
    private function validatePaymentTerms($paymentTerms)
    {
        $fields = ['name', 'start_date', 'finish_date', 'cost'];

        if (!count($paymentTerms)) {
            return "Debes especificar al menos un plazo de pago";
        }

        foreach ($paymentTerms as $paymentTerm) {
            if ($paymentTerm['cost'] <= 0) {
                return "El coste de los plazos de pago no puede ser negativo";
            }
            elseif (!$paymentTerm['name']) {
                return "Debes especificar un nombre para el plazo de pago";
            }

            // Comprobamos si le falta algún campo
            foreach ($fields as $field) {
                if (!array_key_exists($field, $paymentTerm)) {
                    return false;
                }
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
    private function validateCoursesAddedEducationalProgram($request, $educationalProgram)
    {
        $courses = $request->input('courses');

        if (empty($courses)) {
            return;
        }

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
        $realizationStartDate = $request->input('realization_start_date');
        $realizationFinishDate = $request->input('realization_finish_date');

        $coursesNotBetweenRealizationDate = $coursesBd->filter(function ($course) use ($realizationStartDate, $realizationFinishDate) {
            if ($course->realization_start_date && $course->realization_finish_date) {
                return $course->realization_start_date < $realizationStartDate || $course->realization_finish_date > $realizationFinishDate;
            }
        });

        if ($coursesNotBetweenRealizationDate->count()) {
            throw new OperationFailedException(
                'Algunos cursos no están entre las fechas de realización del programa formativo',
                400
            );
        }

        $coursesBelongingOtherEducationalPrograms = $coursesBd->filter(function ($course) use ($educationalProgram) {
            return $course->educational_program_uid && $course->educational_program_uid !== $educationalProgram->uid;
        });

        if ($coursesBelongingOtherEducationalPrograms->count()) {
            throw new OperationFailedException(
                'Algunos cursos pertenecen a otro programa formativo',
                400
            );
        }

        $newCourses = $coursesBd->filter(function ($course) use ($educationalProgram) {
            return $course->educational_program_uid !== $educationalProgram->uid;
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
     * @param  string $callUid El UID de la convocatoria.
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

    public function getEducationalProgram($educationalProgramUid)
    {
        $educationalProgram = EducationalProgramsModel::where('uid', $educationalProgramUid)->with(['courses', 'status', 'tags', 'categories', 'EducationalProgramDocuments', 'contact_emails', 'paymentTerms'])->first();

        if (!$educationalProgram) {
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

        adaptDatesModel($educationalProgram, $dates, false);

        return response()->json($educationalProgram, 200);
    }

    public function searchCoursesWithoutEducationalProgram($search)
    {
        $coursesQuery = CoursesModel::with('status')->where('belongs_to_educational_program', true)
            ->whereHas('status', function ($query) {
                $query->where('code', 'READY_ADD_EDUCATIONAL_PROGRAM');
            });

        if ($search) {
            $coursesQuery->where(function ($query) use ($search) {
                $query->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $courses = $coursesQuery->get();

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
        $educationalProgramsBd = EducationalProgramsModel::whereIn('uid', array_column($changesEducationalProgramsStatuses, "uid"))->with(['status', 'creatorUser'])->get()->keyBy("uid");

        // Excluímos los estados a los que no se pueden cambiar manualmente.
        $statusesEducationalPrograms = EducationalProgramStatusesModel::whereNotIn(
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

        DB::transaction(function () use ($changesEducationalProgramsStatuses, $educationalProgramsBd, $statusesEducationalPrograms) {
            // Recorremos los cursos que nos vienen en el request y los comparamos con los de la base de datos
            foreach ($changesEducationalProgramsStatuses as $changeEducationalProgramStatus) {
                $educationalProgram = $educationalProgramsBd[$changeEducationalProgramStatus['uid']];
                $this->updateStatusEducationalProgram($changeEducationalProgramStatus, $educationalProgram, $statusesEducationalPrograms);

                // Enviamos notificación al creador del programa
                dispatch(new SendChangeStatusEducationalProgramNotification($educationalProgram->toArray()));
            }
        });

        return response()->json(['message' => 'Se han actualizado los estados de los programas formativos correctamente'], 200);
    }

    private function updateStatusEducationalProgram($changeEducationalProgramStatus, $educationalProgramBd, $statusesEducationalPrograms)
    {
        $statusBd = $statusesEducationalPrograms[$changeEducationalProgramStatus['status']];

        $educationalProgramBd->educational_program_status_uid = $statusBd->uid;
        $educationalProgramBd->status_reason = $changeEducationalProgramStatus['reason'] ?? null;

        $educationalProgramBd->save();

        if ($changeEducationalProgramStatus['status'] == 'ACCEPTED_PUBLICATION') {
            $this->sendNotificationCoursesAcceptedPublicationToKafka($educationalProgramBd->courses);
        }
    }

    public function getEducationalProgramStudents(Request $request, $educationalProgramUid)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $educationalProgram = EducationalProgramsModel::where('uid', $educationalProgramUid)->first();

        $query = $educationalProgram->students()->with(
            [
                'educationalProgramDocuments' => function ($query) use ($educationalProgramUid) {
                    $query->whereHas('educationalProgramDocument', function ($query) use ($educationalProgramUid) {
                        $query->where('educational_program_uid', $educationalProgramUid);
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
                    $query->join('educational_programs_students', function ($join) use ($educationalProgramUid) {
                        $join->on('users.uid', '=', 'educational_programs_students.user_uid')
                            ->where('educational_programs_students.educational_program_uid', '=', $educationalProgramUid);
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
            $enroll->uid = generateUuid();
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

        if ($usersenrolled) {
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
        $generalNotificationAutomaticUid = generateUuid();
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
        $generalNotificationAutomaticUser->uid = generateUuid();
        $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomaticUser->user_uid = $userUid;
        $generalNotificationAutomaticUser->save();
    }

    private function saveEmailNotificationAutomatic($educationalProgram, $status, $userUid)
    {
        $emailNotificationAutomatic = new EmailNotificationsAutomaticModel();
        $emailNotificationAutomatic->uid = generateUuid();

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
        $educationalProgramUid = $request->get('educational_program_uid');

        $reader = Reader::createFromPath($file->path());

        foreach ($reader as $key => $row) {

            if ($key > 0) {
                $existingUser = UsersModel::where('email', $row[3])
                    ->first();

                if ($existingUser) {
                    $this->enrollUserCsv($existingUser->uid, $educationalProgramUid);
                } else {
                    $this->validateStudentCsv($row, $key);
                    $this->singUpUser($row, $educationalProgramUid);
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

    public function enrollUserCsv($userUid, $educationalProgramUid)
    {

        $existingEnrollment = EducationalProgramsStudentsModel::where('educational_program_uid', $educationalProgramUid)
            ->where('user_uid', $userUid)
            ->first();

        if (!$existingEnrollment) {

            $enroll = new EducationalProgramsStudentsModel();
            $enroll->uid = generateUuid();
            $enroll->educational_program_uid = $educationalProgramUid;
            $enroll->user_uid = $userUid;
            $enroll->acceptance_status = 'PENDING';
            $messageLog = "Alumno añadido a programa formativo";

            DB::transaction(function () use ($enroll, $messageLog) {
                $enroll->save();
                LogsController::createLog($messageLog, 'Programas formativos', auth()->user()->uid);
            });
        }
    }

    public function singUpUser($row, $educationalProgramUid)
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
            LogsController::createLog($messageLog, 'Programa formativo', auth()->user()->uid);
        });

        $this->enrollUserCsv($newUserUid, $educationalProgramUid);
    }

    public function editionOrDuplicateEducationalProgram(Request $request)
    {

        $educationalProgramUid = $request->input("educationalProgramUid");
        $action = $request->input('action');

        if (!in_array($action, ["edition", "duplication"])) {
            throw new OperationFailedException('Acción no permitida', 400);
        }

        $educationalProgramBd = EducationalProgramsModel::where('uid', $educationalProgramUid)->with(['tags', 'categories', 'EducationalProgramDocuments'])->first();

        if (!$educationalProgramBd) {
            return response()->json(['message' => 'El programa formativo no existe'], 406);
        }

        $newEducationalProgram = $educationalProgramBd->replicate();

        $concatName = $action === "edition" ? "(nueva edición)" : "(copia)";
        $newEducationalProgram->name = $newEducationalProgram->name . " " . $concatName;

        if ($action == "edition") {
            $newEducationalProgram->educational_program_origin_uid = $educationalProgramUid;
        } else {
            $newEducationalProgram->educational_program_origin_uid = null;
        }

        $introductionStatus = EducationalProgramStatusesModel::where('code', 'INTRODUCTION')->first();
        $newEducationalProgram->educational_program_status_uid = $introductionStatus->uid;

        $newEducationalProgramUid = generateUuid();
        DB::transaction(function () use ($newEducationalProgram, $educationalProgramBd, $educationalProgramUid, $action, $newEducationalProgramUid) {
            $newEducationalProgram->uid = $newEducationalProgramUid;
            $newEducationalProgram->identifier = $this->generateIdentificerEducationalProgram();
            $newEducationalProgram->save();

            $this->duplicateEducationalProgramDocuments($educationalProgramBd, $newEducationalProgramUid);

            $courses = CoursesModel::where('educational_program_uid', $educationalProgramUid)->get();

            foreach ($courses as $course) {

                $this->duplicateCourse($course->uid, $newEducationalProgramUid);
            }

            $this->duplicateEducationalProgramTags($educationalProgramBd, $newEducationalProgramUid);

            $this->duplicateEducationalProgramsCategories($educationalProgramBd, $newEducationalProgramUid, $newEducationalProgram);

            if ($educationalProgramBd->payment_mode == "INSTALLMENT_PAYMENT") {
                $this->duplicateEducationalProgramsPaymentTerms($educationalProgramBd, $newEducationalProgramUid);
            }

            if ($action === "edition") {
                $logMessage = 'Creación de edición de programa formativo';
            }
            else {
                $logMessage = 'Duplicación de programa formativo';
            }

            LogsController::createLog($logMessage, 'Programas formativos', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => $action == 'edition' ? 'Edición creada correctamente' : 'Programa duplicado correctamente', 'educational_program_uid' => $newEducationalProgramUid], 200);
    }

    public function deleteInscriptionsEducationalProgram(Request $request)
    {
        $uids = $request->input('uids');

        EducationalProgramsStudentsModel::destroy($uids);

        return response()->json(['message' => 'Inscripciones eliminadas correctamente'], 200);
    }

    private function duplicateCourse($courseUid, $newEducationalProgramUid)
    {
        $courseBd = CoursesModel::where('uid', $courseUid)->with(['teachers', 'tags', 'categories'])->first();

        if (!$courseBd) {
            return response()->json(['message' => 'El curso no existe'], 406);
        }

        $newCourse = $courseBd->replicate();
        $newCourse->identifier = $this->generateIdentificerEducationalProgram();
        $newCourse->educational_program_uid = $newEducationalProgramUid;
        $newCourse->creator_user_uid = auth()->user()->uid;

        $introductionStatus = CourseStatusesModel::where('code', 'ADDED_EDUCATIONAL_PROGRAM')->first();
        $newCourse->course_status_uid = $introductionStatus->uid;
        $newCourse->belongs_to_educational_program = true;

        $newCourseUid = generateUuid();
        $newCourse->uid = $newCourseUid;

        $newCourse->save();

        $this->duplicateCourseTeachers($courseBd, $newCourseUid, $newCourse);

        $this->duplicateCourseTags($courseBd, $newCourseUid);

        $this->duplicateCourseCategories($courseBd, $newCourseUid, $newCourse);
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

        return calculateMedian($studentCounts->toArray());
    }

    private function duplicateEducationalProgramTags($educationalProgramBd, $newEducationalProgramUid)
    {
        $tags = $educationalProgramBd->tags->pluck('tag')->toArray();
        $tagsToAdd = [];
        foreach ($tags as $tag) {
            $tagsToAdd[] = [
                'uid' => generateUuid(),
                'educational_program_uid' => $newEducationalProgramUid,
                'tag' => $tag,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        EducationalProgramTagsModel::insert($tagsToAdd);
    }

    private function duplicateEducationalProgramsPaymentTerms($educationalProgramBd, $newEducationalProgramUid)
    {
        $paymentTerms = $educationalProgramBd->paymentTerms->toArray();
        $paymentTermsToAdd = [];
        foreach ($paymentTerms as $paymentTerm) {
            $paymentTermsToAdd[] = [
                'uid' => generateUuid(),
                'educational_program_uid' => $newEducationalProgramUid,
                'name' => $paymentTerm['name'],
                'start_date' => $paymentTerm['start_date'],
                'finish_date' => $paymentTerm['finish_date'],
                'cost' => $paymentTerm['cost'],
            ];
        }
        EducationalProgramsPaymentTermsModel::insert($paymentTermsToAdd);
    }

    private function duplicateEducationalProgramsCategories($educationalProgramBd, $newEducationalProgramUid, $newEducationalProgram)
    {
        $categories = $educationalProgramBd->categories->pluck('uid')->toArray();
        $categoriesToSync = [];
        foreach ($categories as $categoryUid) {
            $categoriesToSync[] = [
                'uid' => generateUuid(),
                'educational_program_uid' => $newEducationalProgramUid,
                'category_uid' => $categoryUid
            ];
        }
        $newEducationalProgram->categories()->sync($categoriesToSync);
    }
    private function duplicateCourseTeachers($courseBd, $newCourseUid, $newCourse)
    {
        $teachers = $courseBd->teachers->pluck('uid')->toArray();
        $teachersToSync = [];

        foreach ($teachers as $teacherUid) {
            $teachersToSync[$teacherUid] = [
                'uid' => generateUuid(),
                'course_uid' => $newCourseUid,
                'user_uid' => $teacherUid
            ];
        }
        $newCourse->teachers()->sync($teachersToSync);
    }
    private function duplicateCourseTags($courseBd, $newCourseUid)
    {
        $tags = $courseBd->tags->pluck('tag')->toArray();
        $tagsToAdd = [];
        foreach ($tags as $tag) {
            $tagsToAdd[] = [
                'uid' => generateUuid(),
                'course_uid' => $newCourseUid,
                'tag' => $tag,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        CoursesTagsModel::insert($tagsToAdd);
    }
    private function duplicateCourseCategories($courseBd, $newCourseUid, $newCourse)
    {
        $categories = $courseBd->categories->pluck('uid')->toArray();
        $categoriesToSync = [];
        foreach ($categories as $categoryUid) {
            $categoriesToSync[] = [
                'uid' => generateUuid(),
                'course_uid' => $newCourseUid,
                'category_uid' => $categoryUid
            ];
        }
        $newCourse->categories()->sync($categoriesToSync);
    }

    private function duplicateEducationalProgramDocuments($educationalProgramBd, $newEducationalProgramUid)
    {
        foreach ($educationalProgramBd->EducationalProgramDocuments as $document) {
            $newEducationalProgramDocument = $document->replicate();
            $newEducationalProgramDocument->uid = generateUuid();
            $newEducationalProgramDocument->educational_program_uid = $newEducationalProgramUid;
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
        return 'PF-' . str_pad($educationalProgramsCount + 1, 4, '0', STR_PAD_LEFT);
    }
}
