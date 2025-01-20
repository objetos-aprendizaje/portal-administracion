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
use App\Jobs\SendEducationalResourceNotificationToManagements;
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
use App\Models\EducationalResourcesEmbeddingsModel;
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

        $educationalResourcesTypes = EducationalResourceTypesModel::all();
        $categories = CategoriesModel::with('parentCategory')->get();
        $licenseTypes = LicenseTypesModel::get();

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
                "educational_resources_types" => $educationalResourcesTypes,
                "categories" => $categories,
                "submenuselected" => "learning-objects-educational-resources",
                "variables_js" => [
                    "rolesUser" => Auth::user()->roles->pluck('code'),
                    "competencesLearningResults" => $competencesLearningResults,
                    "enabledRecommendationModule" => (bool) app('general_options')['enabled_recommendation_module']
                ],
                "infiniteTree" => true,
                "license_types" => $licenseTypes,
            ]
        );
    }

    public function getResources(Request $request)
    {
        $size = $request->get('size', 10);
        $search = $request->get('search');
        $query = EducationalResourcesModel::with(["status", "type", "categories"])
            ->join('educational_resource_statuses as status', 'educational_resources.status_uid', '=', 'status.uid')
            ->join("educational_resource_types as type", "educational_resources.educational_resource_type_uid", "=", "type.uid")
            ->leftJoin('educational_resources_embeddings', 'educational_resources.uid', '=', 'educational_resources_embeddings.educational_resource_uid')
            ->select('educational_resources.*')
            ->addSelect(DB::raw('CASE WHEN educational_resources_embeddings.embeddings IS NULL THEN 0 ELSE 1 END as embeddings_status'));

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

        if ($filters) {
            $this->applyFilters($filters, $query);
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

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
            } elseif ($filter['database_field'] == "embeddings") {
                $query->where(DB::raw('CASE WHEN educational_resources_embeddings.embeddings IS NULL THEN 0 ELSE 1 END'), '=', $filter['value']);
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }

    /**
     * Obtiene un recurso por uid
     */
    public function getResource($resourceUid)
    {
        $resource = EducationalResourcesModel::where('uid', $resourceUid)->with([
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

        $uidResource = $request->educational_resource_uid;
        $isNew = !$uidResource;

        if ($isNew) {
            $resource = new EducationalResourcesModel();
            $resource->uid = generateUuid();
            $resource->identifier = $this->generateIdentificerEducationalResources();
            $resource->creator_user_uid = auth()->user()->uid;
        } else {
            $resource = EducationalResourcesModel::where('uid', $uidResource)->with(['categories', 'embeddings'])->first();
        }

        // Comprobamos el nuevo estado que le corresponde al recurso.
        $action = $request->input('action');

        if ($isNew) {
            $newResourceStatus = $this->getStatusResource($action);
        } else {
            $actualStatusCourse = $resource->status['code'];
            $newResourceStatus = $this->getStatusResource($action, $actualStatusCourse);
        }

        $resource->license_type_uid = $request->input('license_type_uid');

        return DB::transaction(function () use ($request, $resource, $isNew, $newResourceStatus) {
            $embeddings = $this->generateResourceEmbeddings($request, $resource);

            $this->handleResourceImage($request, $resource);
            $this->fillResourceDetails($request, $resource);
            $this->handleResourceWay($request, $resource);

            if ($newResourceStatus) {
                $resource->status_uid = $newResourceStatus->uid;
                if ($newResourceStatus->code == "PENDING_APPROVAL") {
                    dispatch(new SendEducationalResourceNotificationToManagements($resource->toArray()));
                }
            }

            $resource->save();

            if ($embeddings) {
                EducationalResourcesEmbeddingsModel::updateOrCreate(
                    ['educational_resource_uid' => $resource->uid],
                    ['embeddings' => $embeddings]
                );
            }

            $this->handleTags($request, $resource);
            $this->handleMetadata($request, $resource);
            $this->handleCategories($request, $resource);
            $this->handleEmails($request, $resource);
            $this->handleLearningResults($request, $resource);
            $this->createLog($isNew, $resource->title);

            return response()->json(['message' => 'Recurso añadido correctamente']);
        }, 5);
    }

    public function regenerateEmbeddings(Request $request)
    {
        $educationalResourcesUids = $request->input('educational_resources_uids');
        $educationalResources = EducationalResourcesModel::whereIn('uid', $educationalResourcesUids)->get();

        foreach ($educationalResources as $educationalResource) {
            $this->embeddingsService->generateEmbeddingForEducationalResource($educationalResource);
        }

        return response()->json(['message' => 'Se han regenerado los embeddings correctamente'], 200);
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
            return $embeddings ?: $resourceBd->embeddings->embeddings ?? null;
        }

        return $resourceBd->embeddings->embeddings;
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

        $resourceWay = $request->resource_way;

        $rules = [
            'title' => 'required|string',
            'educational_resource_type_uid' => 'required|string',
            'resource_way' => 'required|string',
        ];

        $uidResource = $request->educational_resource_uid;

        // En función del tipo de recurso, establecemos como obligatorio adjuntar un archivo o URL
        if (!$uidResource && $resourceWay == 'FILE') {
            $rules['resource_input_file'] = 'required';
        } elseif (!$uidResource && $resourceWay == 'URL') {
            $rules['resource_url'] = 'required|url';
        } elseif ($resourceWay == 'URL') {
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

    private function getStatusResource($action, $actualStatusCourse = null)
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
    private function checkAutomaticApproval()
    {
        // Comprobamos si está de forma específica
        $teacherApproved = AutomaticResourceAprovalUsersModel::where('user_uid', auth()->user()->uid)->exists();
        if ($teacherApproved) {
            return true;
        }

        // Comprobamos si está de forma global
        if (app('general_options')['necessary_approval_resources']) {
            return true;
        }

        return false;
    }

    private function handleResourceImage($request, $resource)
    {
        $resourceImageInputFile = $request->file('resource_image_input_file');
        if ($resourceImageInputFile) {
            $resource->image_path = saveFile($resourceImageInputFile, "images/resources-images", null, true);
        }
    }

    private function fillResourceDetails($request, $resource)
    {
        $resource->fill($request->only([
            "title",
            "description",
            "educational_resource_type_uid",
            "license_type",
            "resource_way"
        ]));
    }

    private function handleResourceWay($request, $resource)
    {
        if (in_array($request->resource_way, ['FILE', 'IMAGE', 'PDF', 'VIDEO', 'AUDIO'])) {
            $resourceInputFile = $request->file('resource_input_file');

            if ($resourceInputFile) {
                $resource->resource_path = saveFile($resourceInputFile, "attachments/resources", null, true);
            }

            $resource->resource_url = null;
        } elseif ($request->resource_way == 'URL') {
            $resource->resource_url = $request->resource_url;
            $resource->resource_path = null;
        }
    }

    private function handleTags($request, $resource)
    {
        $tags = $request->input('tags');
        $tags = json_decode($tags, true);

        if (!empty($tags)) {
            $currentTags = EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->pluck('tag')->toArray();

            $tagsToAdd = array_diff($tags, $currentTags);
            $tagsToDelete = array_diff($currentTags, $tags);

            EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->whereIn('tag', $tagsToDelete)->delete();

            $insertData = [];
            foreach ($tagsToAdd as $tag) {
                $insertData[] = [
                    'uid' => generateUuid(),
                    'educational_resource_uid' => $resource->uid,
                    'tag' => $tag
                ];
            }

            EducationalResourcesTagsModel::insert($insertData);
        } else {
            EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->delete();
        }
    }

    private function handleEmails($request, $resource)
    {
        $contactEmails = $request->input('contact_emails');
        $contactEmails = json_decode($contactEmails, true);

        if (!empty($contactEmails)) {
            $currentContactEmails = EducationalResourcesEmailContactsModel::where('educational_resource_uid', $resource->uid)->pluck('email')->toArray();

            $contactEmailsToAdd = array_diff($contactEmails, $currentContactEmails);
            $contactEmailsToDelete = array_diff($currentContactEmails, $contactEmails);

            EducationalResourcesEmailContactsModel::where('educational_resource_uid', $resource->uid)->whereIn('email', $contactEmailsToDelete)->delete();

            $insertData = [];
            foreach ($contactEmailsToAdd as $contactEmail) {
                $insertData[] = [
                    'uid' => generateUuid(),
                    'educational_resource_uid' => $resource->uid,
                    'email' => $contactEmail
                ];
            }

            EducationalResourcesEmailContactsModel::insert($insertData);
        } else {
            EducationalResourcesEmailContactsModel::where('educational_resource_uid', $resource->uid)->delete();
        }
    }

    private function handleMetadata($request, $resource)
    {
        $metadata = $request->input('metadata');
        $metadata = json_decode($metadata, true);
        $resource->updateMetadata($metadata);
    }

    private function handleCategories($request, $resource)
    {
        $categories = $request->input('categories');
        $categories = json_decode($categories, true);

        $categoriesBd = CategoriesModel::whereIn('uid', $categories)->get()->pluck('uid');

        EducationalResourceCategoriesModel::where('educational_resource_uid', $resource->uid)->delete();

        $categoriesToSync = [];
        foreach ($categoriesBd as $categoryUid) {
            $categoriesToSync[] = [
                'uid' => generateUuid(),
                'educational_resource_uid' => $resource->uid,
                'category_uid' => $categoryUid
            ];
        }

        $resource->categories()->sync($categoriesToSync);
    }

    private function createLog($isNew, $resourceName)
    {
        $logMessage = $isNew ? 'Recurso educativo añadido: ' : 'Recurso educativo actualizado: ';
        $logMessage .= $resourceName;
        LogsController::createLog($logMessage, 'Recursos educativos', auth()->user()->uid);
    }

    public function deleteResources()
    {
        $resourcesUids = request()->input('resourcesUids');
        $educationalResources = EducationalResourcesModel::whereIn('uid', $resourcesUids)->get();

        DB::transaction(function () use ($educationalResources) {
            foreach ($educationalResources as $educationalResource) {
                $educationalResource->delete();
                LogsController::createLog("Eliminación de recurso educativo: " . $educationalResource->title, 'Recursos educativos', auth()->user()->uid);
            }
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
        $resourcesBd = EducationalResourcesModel::whereIn('uid', array_column($changesResourcesStatuses, "uid"))
            ->with([
                "status",
                "tags",
                "categories"
            ])
            ->get()->keyBy("uid");

        // Actualizamos los estados de los recursos y enviamos los recursos a la api de búsqueda
        $this->updateEducationalResourcesStatuses($changesResourcesStatuses, $resourcesBd);

        return response()->json(['message' => 'Se han actualizado los estados de los recursos correctamente'], 200);
    }

    private function updateEducationalResourcesStatuses($changesResourcesStatuses, $resourcesBd)
    {
        $statusesResources = EducationalResourceStatusesModel::get()->keyBy("code");

        $studentsUsers = $this->getStudentsUsers();

        DB::transaction(function () use ($changesResourcesStatuses, $resourcesBd, $statusesResources, $studentsUsers) {
            foreach ($changesResourcesStatuses as $changeStatus) {
                $newStatus = $statusesResources[$changeStatus['status']];
                $resource = $resourcesBd[$changeStatus['uid']];

                $resource->status_uid = $newStatus->uid;
                $resource->save();

                if ($newStatus->code == 'PUBLISHED') {
                    $this->sendEmailNotifications($resource, $studentsUsers);
                    $this->sendGeneralNotifications($resource, $studentsUsers);
                    if (env('ENABLED_API_SEARCH')) {
                        $this->sendResourcesToApiSearch($resource);
                    }
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
            return !$commonCategories->isEmpty() && !$user->automaticGeneralNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS');
        });
                     
        $type = AutomaticNotificationTypesModel::where('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS')->first();
        $generalNotificationAutomaticUid = generateUuid();
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
                "uid" => generateUuid(),
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
                "uid" => generateUuid(),
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
        return UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })
            ->with([
                "categories",
                "automaticGeneralNotificationsTypesDisabled",
                "automaticEmailNotificationsTypesDisabled"
            ])
            ->get();
    }

    private function generateIdentificerEducationalResources()
    {
        $educationalProgramsCount = EducationalResourcesModel::count();
        return 'RE-' . str_pad($educationalProgramsCount + 1, 4, '0', STR_PAD_LEFT);
    }

    private function getCompetencesFrameworks()
    {

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

    private function mapStructureFramework($obj, $type = "competence")
    {
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
