<?php

namespace App\Http\Controllers\LearningObjects;

use App\Models\UsersModel;
use Illuminate\Http\Request;
use App\Models\CategoriesModel;
use App\Models\CompetencesModel;
use App\Models\LicenseTypesModel;
use Illuminate\Support\Facades\DB;
use App\Models\LearningResultsModel;
use Illuminate\Support\Facades\Auth;
use App\Models\EducationalResourcesModel;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\OperationFailedException;
use App\Http\Controllers\Logs\LogsController;
use App\Models\EducationalResourcesTagsModel;
use App\Models\EducationalResourceTypesModel;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalResourceStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\AutomaticResourceAprovalUsersModel;
use App\Models\CompetenceFrameworksModel;
use App\Models\EducationalResourceCategoriesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EducationalResourcesEmailContactsModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Models\EducationalResourcesLearningResultsModel;
use App\Services\EmbeddingsService;

class EducationalResourcesController extends BaseController
{
    protected $embeddingsService;

    public function __construct(EmbeddingsService $embeddingsService)
    {
        $this->embeddingsService = $embeddingsService;
    }

    public function index()
    {

        $educational_resources_types = EducationalResourceTypesModel::all()->toArray();
        $categories = CategoriesModel::with('parentCategory')->get()->toArray();
        $license_types = LicenseTypesModel::get()->toArray();

        $competencesLearningResults = $this->getCompetencesFrameworks();

        return view(
            'learning_objects.educational_resources.index',
            [
                "page_name" => "Recursos educativos",
                "page_title" => "Recursos educativos",
                "resources" => [
                    "resources/js/learning_objects_module/educational_resources.js"
                ],
                "tabulator" => true,
                "tomselect" => true,
                "educational_resources_types" => $educational_resources_types,
                "categories" => $categories,
                "submenuselected" => "learning-objects-educational-resources",
                "variables_js" => [
                    "rolesUser" => Auth::user()->roles->pluck('code'),
                    "competencesLearningResults" => $competencesLearningResults
                ],
                "infiniteTree" => true,
                "license_types" => $license_types,
            ]
        );
    }

    public function getResources(Request $request)
    {
        $size = $request->get('size', 10);
        $search = $request->get('search');
        $query = EducationalResourcesModel::with(["status", "type", "categories"])
            ->join('educational_resource_statuses as status', 'educational_resources.status_uid', '=', 'status.uid')
            ->join("educational_resource_types as type", "educational_resources.educational_resource_type_uid", "=", "type.uid");

        $rolesUser = Auth::user()->roles->pluck('code');

        // Si sólo es docente y no tiene ningún otro rol, se le muestran sólo los recursos que ha creado
        if ($rolesUser->count() == 1 && $rolesUser->contains('TEACHER')) {
            $query->where('educational_resources.creator_user_uid', Auth::user()->uid);
        }

        $sort = $request->get('sort');
        $filters = $request->get('filters');

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('educational_resources.description', 'ILIKE', "%{$search}%")
                ->orWhere('identifier', $search);
        }

        if ($filters) $this->applyFilters($filters, $query);

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $query->select("educational_resources.*");

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == "categories") {
                $query->whereHas('categories', function ($query) use ($filter) {
                    $query->whereIn('categories.uid', $filter['value']);
                });
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }

    /**
     * Obtiene un recurso por uid
     */
    public function getResource($resource_uid)
    {

        if (!$resource_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $resource = EducationalResourcesModel::where('uid', $resource_uid)->with([
            "creatorUser",
            "status",
            "type",
            "tags",
            "metadata",
            "categories",
            "contact_emails",
            "learningResults"
        ])
            ->first();

        if (!$resource) {
            return response()->json(['message' => 'El recurso no existe'], 406);
        }

        return response()->json($resource, 200);
    }

    public function saveResource(Request $request)
    {

        $errorsValidators = $this->validateResource($request);

        if (!empty($errorsValidators)) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errorsValidators], 422);
        }

        $uid_resource = $request->educational_resource_uid;
        $isNew = !$uid_resource;

        if ($isNew) {
            $resource = new EducationalResourcesModel();
            $resource->uid = generate_uuid();
            $resource->identifier = $this->generateIdentificerEducationalResources();
            $resource->creator_user_uid = auth()->user()->uid;
        } else {
            $resource = EducationalResourcesModel::where('uid', $uid_resource)->with('categories')->first();
        }

        // Comprobamos el nuevo estado que le corresponde al curso.
        $action = $request->input('action');

        if ($isNew) {
            $new_resource_status = $this->getStatusResource($action);
            if ($new_resource_status) $resource->status_uid = $new_resource_status->uid;
        } else {
            $actual_status_course = $resource->status['code'];
            $new_resource_status = $this->getStatusResource($action, $actual_status_course);
            if ($new_resource_status) $resource->status_uid = $new_resource_status->uid;
        }

        $resource->license_type_uid = $request->input('license_type_uid');

        return DB::transaction(function () use ($request, $resource, $isNew) {
            $embeddings = $this->generateResourceEmbeddings($request, $resource);

            $this->handleResourceImage($request, $resource);
            $this->fillResourceDetails($request, $resource);
            $this->handleResourceWay($request, $resource);
            $resource->save();

            $resource->embeddings = $embeddings;
            $resource->save();

            $this->handleTags($request, $resource);
            $this->handleMetadata($request, $resource);
            $this->handleCategories($request, $resource);
            $this->handleEmails($request, $resource);
            $this->handleLearningResults($request, $resource);
            $this->createLog($isNew);

            return response()->json(['message' => 'Recurso añadido correctamente']);
        }, 5);
    }

    /**
     *
     * Si el recurso no tiene embeddings, se generan.
     * Si el título o la descripción han cambiado, se generan nuevos embeddings.
     * Si no se cumplen las condiciones anteriores o falla la API, se devuelven los embeddings actuales.
     */
    private function generateResourceEmbeddings($request, $resourceBd)
    {
        $title = $request->input('title');
        $description = $request->input('description');

        if (!$resourceBd->embeddings || $title != $resourceBd->title || $description != $resourceBd->description) {
            $embeddings = $this->embeddingsService->getEmbedding($title . ' ' . $description);
            return $embeddings ?: $resourceBd->embeddings;
        }

        return $resourceBd->embeddings;
    }

    private function handleLearningResults($request, $resource)
    {
        $learningResults = $request->input('learning_results');
        $learningResults = json_decode($learningResults, true);

        $learningResultsBd = LearningResultsModel::whereIn('uid', $learningResults)->get()->pluck('uid');
        EducationalResourcesLearningResultsModel::where('educational_resource_uid', $resource->uid)->delete();

        $learningResultsToSync = [];
        foreach ($learningResultsBd as $learningResult) {
            $learningResultsToSync[] = [
                'educational_resource_uid' => $resource->uid,
                'learning_result_uid' => $learningResult
            ];
        }

        $resource->learningResults()->sync($learningResultsToSync);
    }

    private function validateResource($request)
    {

        // Resultados de aprendizaje
        $learningResults = $request->input('learning_results');
        $learningResults = json_decode($learningResults, true);
        if (count($learningResults) > 100) {
            throw new OperationFailedException('No se pueden seleccionar más de 100 resultados de aprendizaje');
        }

        $messages = [
            'title.required' => 'El título del recurso es obligatorio',
            'educational_resource_type_uid.required' => 'El tipo de recurso es obligatorio',
            'resource_way' => 'La forma del recurso es obligatoria',
            'resource_input_file.required' => 'Debes adjuntar un fichero',
            'resource_url.required' => 'Debes especificar la URL del recurso',
            'resource_url.url' => 'La URL no es válida'
        ];

        $resource_way = $request->resource_way;

        $rules = [
            'title' => 'required|string',
            'educational_resource_type_uid' => 'required|string',
            'resource_way' => 'required|string',
        ];

        $uid_resource = $request->educational_resource_uid;

        // En función del tipo de recurso, establecemos como obligatorio adjuntar un archivo o URL
        if (!$uid_resource && $resource_way == 'FILE') {
            $rules['resource_input_file'] = 'required';
        } else if (!$uid_resource && $resource_way == 'URL') {
            $rules['resource_url'] = 'required|url';
        } else if ($resource_way == 'URL') {
            $rules['resource_url'] = 'required|url';
        }


        $validator = Validator::make($request->all(), $rules, $messages);

        $errorsValidators = [];
        if ($validator->fails()) {
            $errorsValidators = array_merge($errorsValidators, $validator->errors()->toArray());
        }

        $metadata = json_decode($request->input('metadata'), true);

        $validator = Validator::make(['metadata' => $metadata], [
            'metadata.*.metadata_key' => 'required|string|min:1',
            'metadata.*.metadata_value' => 'required|string|min:1',
        ], [
            'metadata.*.metadata_key.required' => 'Debes especificar un nombre',
            'metadata.*.metadata_key.min' => 'Debes especificar un nombre',
            'metadata.*.metadata_value.required' => 'Debes especificar un valor',
            'metadata.*.metadata_value.string' => 'Debes especificar un valor',
            'metadata.*.metadata_value.min' => 'Debes especificar un valor',
        ]);

        if ($validator->fails()) {
            $errorsValidators = array_merge($errorsValidators, $validator->errors()->toArray());
        }

        return $errorsValidators;
    }

    function getStatusResource($action, $actualStatusCourse = null)
    {
        $statusResources = EducationalResourceStatusesModel::whereIn('code', [
            'INTRODUCTION',
            'PENDING_APPROVAL',
            'PUBLISHED',
            'UNDER_CORRECTION_APPROVAL'
        ])->get()->keyBy('code');

        $automaticApproval = $this->checkAutomaticApproval();

        if ($action === "submit" && (!$actualStatusCourse || $actualStatusCourse === "INTRODUCTION" || $actualStatusCourse === "UNDER_CORRECTION_APPROVAL")) {
            return $automaticApproval ? $statusResources['PUBLISHED'] : $statusResources['PENDING_APPROVAL'];
        } elseif ($action === "draft" && !$actualStatusCourse) {
            return $statusResources['INTRODUCTION'];
        }
    }

    // Si el gestor ha definido de forma global o a este usuario específico que se le aprueben automáticamente los recursos
    function checkAutomaticApproval()
    {
        // Comprobamos si está de forma global
        if (app('general_options')['necessary_approval_resources'] == 0) return true;

        // Comprobamos si está de forma específica
        $teacherApproved = AutomaticResourceAprovalUsersModel::where('user_uid', auth()->user()->uid)->exists();
        if ($teacherApproved) return true;

        return false;
    }

    function handleResourceImage($request, $resource)
    {
        $resource_image_input_file = $request->file('resource_image_input_file');
        if ($resource_image_input_file) {
            $resource->image_path = saveFile($resource_image_input_file, "images/resources-images", null, true);
        }
    }

    function fillResourceDetails($request, $resource)
    {
        $resource->fill($request->only([
            "title", "description", "educational_resource_type_uid", "license_type", "resource_way"
        ]));
    }

    function handleResourceWay($request, $resource)
    {
        if (in_array($request->resource_way, ['FILE', 'IMAGE', 'PDF', 'VIDEO', 'AUDIO'])) {
            $resource_input_file = $request->file('resource_input_file');

            if ($resource_input_file) $resource->resource_path = saveFile($resource_input_file, "attachments/resources", null, true);

            $resource->resource_url = null;
        } elseif ($request->resource_way == 'URL') {
            $resource->resource_url = $request->resource_url;
            $resource->resource_path = null;
        }
    }

    function handleTags($request, $resource)
    {
        $tags = $request->input('tags');
        $tags = json_decode($tags, true);

        if (!empty($tags)) {
            $current_tags = EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->pluck('tag')->toArray();

            $tags_to_add = array_diff($tags, $current_tags);
            $tags_to_delete = array_diff($current_tags, $tags);

            EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->whereIn('tag', $tags_to_delete)->delete();

            $insertData = [];
            foreach ($tags_to_add as $tag) {
                $insertData[] = [
                    'uid' => generate_uuid(),
                    'educational_resource_uid' => $resource->uid,
                    'tag' => $tag
                ];
            }

            EducationalResourcesTagsModel::insert($insertData);
        } else {
            EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->delete();
        }
    }

    function handleEmails($request, $resource)
    {
        $contact_emails = $request->input('contact_emails');
        $contact_emails = json_decode($contact_emails, true);

        if (!empty($contact_emails)) {
            $current_contact_emails = EducationalResourcesEmailContactsModel::where('educational_resource_uid', $resource->uid)->pluck('email')->toArray();

            $contact_emails_to_add = array_diff($contact_emails, $current_contact_emails);
            $contact_emails_to_delete = array_diff($current_contact_emails, $contact_emails);

            EducationalResourcesEmailContactsModel::where('educational_resource_uid', $resource->uid)->whereIn('email', $contact_emails_to_delete)->delete();

            $insertData = [];
            foreach ($contact_emails_to_add as $contact_email) {
                $insertData[] = [
                    'uid' => generate_uuid(),
                    'educational_resource_uid' => $resource->uid,
                    'email' => $contact_email
                ];
            }

            EducationalResourcesEmailContactsModel::insert($insertData);
        } else {
            EducationalResourcesEmailContactsModel::where('educational_resource_uid', $resource->uid)->delete();
        }
    }

    function handleMetadata($request, $resource)
    {
        $metadata = $request->input('metadata');
        $metadata = json_decode($metadata, true);
        $resource->updateMetadata($metadata);
    }

    function handleCategories($request, $resource)
    {
        $categories = $request->input('categories');
        $categories = json_decode($categories, true);

        $categories_bd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

        EducationalResourceCategoriesModel::where('educational_resource_uid', $resource->uid)->delete();

        $categories_to_sync = [];
        foreach ($categories_bd as $category_uid) {
            $categories_to_sync[] = [
                'uid' => generate_uuid(),
                'educational_resource_uid' => $resource->uid,
                'category_uid' => $category_uid
            ];
        }

        $resource->categories()->sync($categories_to_sync);
    }

    function createLog($isNew)
    {
        $logMessage = $isNew ? 'Recurso educativo añadido' : 'Recurso educativo actualizado';
        LogsController::createLog($logMessage, 'Recursos educativos', auth()->user()->uid);
    }

    public function deleteResources()
    {
        $resources_uids = request()->input('resourcesUids');

        DB::transaction(function () use ($resources_uids) {
            EducationalResourcesModel::whereIn('uid', $resources_uids)->delete();
            LogsController::createLog("Eliminación de recursos educativos", 'Recursos educativos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Recursos eliminados correctamente']);
    }


    public function changeStatusesResources(Request $request)
    {
        $changesResourcesStatuses = $request->input('changesResourcesStatuses');

        if (!$changesResourcesStatuses) {
            return response()->json(['message' => 'No se han enviado los datos correctamente'], 406);
        }

        // Sacamos los recursos de la base de datos
        $resources_bd = EducationalResourcesModel::whereIn('uid', array_column($changesResourcesStatuses, "uid"))
            ->with([
                "status",
                "tags",
                "categories"
            ])
            ->get()->keyBy("uid");

        // Actualizamos los estados de los recursos y enviamos los recursos a la api de búsqueda
        $this->updateEducationalResourcesStatuses($changesResourcesStatuses, $resources_bd);

        return response()->json(['message' => 'Se han actualizado los estados de los recursos correctamente'], 200);
    }

    private function updateEducationalResourcesStatuses($changesResourcesStatuses, $resourcesBd)
    {
        $statuses_resources = EducationalResourceStatusesModel::get()->keyBy("code");

        $studentsUsers = $this->getStudentsUsers();

        DB::transaction(function () use ($changesResourcesStatuses, $resourcesBd, $statuses_resources, $studentsUsers) {
            foreach ($changesResourcesStatuses as $changeStatus) {
                $newStatus = $statuses_resources[$changeStatus['status']];
                $resource = $resourcesBd[$changeStatus['uid']];

                $resource->status_uid = $newStatus->uid;
                $resource->save();

                if ($newStatus->code == 'PUBLISHED') {
                    $this->sendEmailNotifications($resource, $studentsUsers);
                    $this->sendGeneralNotifications($resource, $studentsUsers);
                    if (env('ENABLED_API_SEARCH')) $this->sendResourcesToApiSearch($resource);
                }
            }

            LogsController::createLog("Cambio de estado de recursos educativos", 'Recursos educativos', auth()->user()->uid);
        });
    }

    private function sendGeneralNotifications($resource, $studentsUsers)
    {

        $resourceCategoryUids = $resource->categories->pluck("uid");

        // Filtramos los usuarios y nos quedamos con los que tengan notificaciones por email
        $usersFiltered = $studentsUsers->filter(function ($user) use ($resourceCategoryUids) {
            // Extrae las UIDs de las categorías del usuario
            $userCategoryUids = $user->categories->pluck("uid");
            // Encuentra la intersección de las categorías del usuario y las categorías del recurso
            $commonCategories = $userCategoryUids->intersect($resourceCategoryUids);
            // Si hay categorías en común, el usuario debe ser incluido en el filtro
            return !$commonCategories->isEmpty() && !$user->automaticGeneralNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS');
        });

        //Todo: se agregó esto ya que el campo automatic_notification_type_uid es obligatorio si no da error 500,
        //Todo solo se hizo para poder correr la prueba unitaria
        $type = AutomaticNotificationTypesModel::where('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS' )->first();
        $generalNotificationAutomaticUid = generate_uuid();
        $generalAutomaticNotification = new GeneralNotificationsAutomaticModel();
        $generalAutomaticNotification->uid = $generalNotificationAutomaticUid;
        $generalAutomaticNotification->title = "Nuevo recurso educativo";
        $generalAutomaticNotification->description = "Se ha añadido un nuevo recurso educativo: " . $resource->title;
        $generalAutomaticNotification->entity = "new_educational_resource";
        $generalAutomaticNotification->entity_uid = $resource->uid;
        $generalAutomaticNotification->created_at = now();
        $generalAutomaticNotification->automatic_notification_type_uid = $type->uid;
        $generalAutomaticNotification->save();

        $dataInsert = [];
        foreach ($usersFiltered as $user) {
            $dataInsert[] = [
                "uid" => generate_uuid(),
                "general_notifications_automatic_uid" => $generalNotificationAutomaticUid,
                "user_uid" => $user->uid,
            ];
        }

        $dataInsert = array_chunk($dataInsert, 500);
        foreach ($dataInsert as $data) {
            GeneralNotificationsAutomaticUsersModel::insert($data);
        }
    }

    private function sendEmailNotifications($resource, $studentsUsers)
    {
        $resourceCategoryUids = $resource->categories->pluck("uid");

        // Filtramos los usuarios y nos quedamos con los que tengan notificaciones por email
        $usersFiltered = $studentsUsers->filter(function ($user) use ($resourceCategoryUids) {
            // Extrae las UIDs de las categorías del usuario
            $userCategoryUids = $user->categories->pluck("uid");
            // Encuentra la intersección de las categorías del usuario y las categorías del recurso
            $commonCategories = $userCategoryUids->intersect($resourceCategoryUids);
            // Si hay categorías en común, el usuario debe ser incluido en el filtro
            return !$commonCategories->isEmpty() &&
                !$user->automaticEmailNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS');
        });

        $dataInsert = [];
        foreach ($usersFiltered as $user) {
            $dataInsert[] = [
                "uid" => generate_uuid(),
                "user_uid" => $user->uid,
                "template" => "recommended_educational_resource_user",
                'subject' => 'Nuevo recurso educativo',
                "parameters" => json_encode([
                    "resource_title" => $resource->title,
                ]),
            ];
        }

        $dataInsert = array_chunk($dataInsert, 500);
        foreach ($dataInsert as $data) {
            EmailNotificationsAutomaticModel::insert($data);
        }
    }

    private function sendResourcesToApiSearch($resource)
    {
        $endpoint = env('API_SEARCH_URL') . '/submit_learning_objects';
        $headers = [
            'API-KEY' => env('API_SEARCH_KEY'),
        ];

        $data[] = [
            "uid" => $resource->uid,
            "title" => $resource->title,
            "description" => $resource->description ?? "",
            "tags" => json_encode($resource->tags->toArray())
        ];

        guzzle_call($endpoint, $data, $headers, 'POST');
    }

    private function getStudentsUsers()
    {
        $students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })
            ->with([
                "categories",
                "automaticGeneralNotificationsTypesDisabled",
                "automaticEmailNotificationsTypesDisabled"
            ])
            ->get();

        return $students;
    }

    private function generateIdentificerEducationalResources()
    {
        $educationalProgramsCount = EducationalResourcesModel::count();
        $identifier = 'RE-' . str_pad($educationalProgramsCount + 1, 4, '0', STR_PAD_LEFT);

        return $identifier;
    }

    private function getCompetencesFrameworks() {

        $competenceFrameworks = CompetenceFrameworksModel::with([
            'levels',
            'allSubcompetences',
            'allSubcompetences.learningResults',
            'allSubcompetences.allSubcompetences',
            'allSubcompetences.allSubcompetences.learningResults'
        ])->get();

        $competencesLearningResults = [];
        foreach ($competenceFrameworks as $competenceFramework) {
            $competencesLearningResults[] = $this->mapStructureFramework($competenceFramework->toArray(), "competence_framework");
        }

        return $competencesLearningResults;
    }

    private function mapStructureFramework($obj, $type = "competence") {
        // Crear un nuevo objeto con los campos necesarios
        $mappedObj = [
            'id' => $obj['uid'],
            'name' => $obj['name'],
            'children' => [],
            'type' => $type,
            'showCheckbox' => true,
        ];

        // Si hay subcompetencias, recursivamente mapéalas
        if (isset($obj['all_subcompetences']) && count($obj['all_subcompetences']) > 0) {
            foreach ($obj['all_subcompetences'] as $sub) {
                $mappedObj['children'][] = $this->mapStructureFramework($sub);
            }
        }

        // Si hay resultados de aprendizaje, agrégalos también
        if (isset($obj['learning_results']) && count($obj['learning_results']) > 0) {
            foreach ($obj['learning_results'] as $lr) {
                $mappedObj['children'][] = [
                    'id' => $lr['uid'],
                    'name' => $lr['name'],
                    'type' => 'learning_result',
                    'showCheckbox' => true,
                    'disabled' => false,
                ];
            }
        }

        // Devuelve el objeto mapeado
        return $mappedObj;
    }
}
