<?php

namespace App\Http\Controllers\LearningObjects;

use App\Models\CategoriesModel;
use App\Models\EducationalResourceCategoriesModel;
use Illuminate\Http\Request;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourcesTagsModel;
use App\Models\EducationalResourceStatusesModel;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;


class EducationalResourcesController extends BaseController
{
    public function index()
    {

        $educational_resources_types = EducationalResourceTypesModel::all()->toArray();
        $categories = CategoriesModel::with('parentCategory')->get()->toArray();

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
                "categories" => $categories
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

        $sort = $request->get('sort');
        $filters = $request->get('filters');

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
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

        $resource = EducationalResourcesModel::where('uid', $resource_uid)->with(["creatorUser", "status", "type", "tags", "metadata", "categories"])->first();

        if (!$resource) {
            return response()->json(['message' => 'El recurso no existe'], 406);
        }

        return response()->json($resource, 200);
    }

    public function saveResource(Request $request)
    {

        $errorsValidators = $this->validateResource($request);

        if (!empty($errorsValidators)) {
            return response()->json(['errors' => $errorsValidators], 422);
        }

        $uid_resource = $request->educational_resource_uid;
        $isNew = !$uid_resource;

        if ($isNew) {
            $resource = new EducationalResourcesModel();
            $resource->uid = generate_uuid();
            $resource->creator_user_uid = auth()->user()->uid;
        } else {
            $resource = EducationalResourcesModel::where('uid', $uid_resource)->with('categories')->first();
        }

        // Comprobamos el nuevo estado que le corresponde al curso.
        $action = $request->input('action');

        if ($isNew) {
            $new_resource_status = $this->getNewResourceStatus($action);
            $resource->status_uid = $new_resource_status->uid;
        } else {
            $actual_status_course = $resource->status['code'];
            $new_resource_status = $this->getExistingResourceStatus($actual_status_course, $action);
            $resource->status_uid = $new_resource_status->uid;
        }

        return DB::transaction(function () use ($request, $resource, $isNew) {
            $this->handleResourceImage($request, $resource);
            $this->fillResourceDetails($request, $resource);
            $this->handleResourceWay($request, $resource);
            $resource->save();
            $this->handleTags($request, $resource);
            $this->handleMetadata($request, $resource);
            $this->handleCategories($request, $resource);
            $this->createLog($isNew);

            return response()->json(['message' => 'Recurso añadido correctamente']);
        }, 5);
    }

    private function validateResource($request)
    {
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


    function getExistingResourceStatus($actual_status_course, $action)
    {
        if (!in_array($actual_status_course, ['INTRODUCTION', 'UNDER_CORRECTION_APPROVAL'])) abort(403);

        $pending_approval = EducationalResourceStatusesModel::where('code', 'PENDING_APPROVAL')->first();
        $introduction = EducationalResourceStatusesModel::where('code', 'INTRODUCTION')->first();

        if ($actual_status_course == 'UNDER_CORRECTION_APPROVAL') {
            return $pending_approval;
        } else if ($actual_status_course == 'INTRODUCTION' && $action == "submit") {
            return $pending_approval;
        } else {
            return $introduction;
        }
    }

    function getNewResourceStatus($action)
    {

        if ($action == "submit") {
            $pending_approval = EducationalResourceStatusesModel::where('code', 'PENDING_APPROVAL')->first();
            return $pending_approval;
        } else {
            $introduction = EducationalResourceStatusesModel::where('code', 'INTRODUCTION')->first();
            return $introduction;
        }
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
        if ($request->resource_way == 'FILE') {
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
        $resources_bd = EducationalResourcesModel::whereIn('uid', array_column($changesResourcesStatuses, "uid"))->with('status')->with("tags")->get();

        // Todos los estados de los recursos
        $statuses_resources = EducationalResourceStatusesModel::all();

        // Preparamos un array con los nuevos estados para los recursos y un array para enviarlo a la api de búsqueda
        list($updated_resources_data, $data_search_api) = $this->prepareData($changesResourcesStatuses, $resources_bd, $statuses_resources);

        // Actualizamos los estados de los recursos y enviamos los recursos a la api de búsqueda
        $this->updateDatabase($updated_resources_data, $data_search_api);

        return response()->json(['message' => 'Se han actualizado los estados de los recursos correctamente'], 200);
    }

    private function prepareData($changesResourcesStatuses, $resources_bd, $statuses_resources)
    {
        $updated_resources_data = [];
        $data_search_api = [];

        foreach ($changesResourcesStatuses as $changeResourceStatus) {
            // Sacamos el recurso del array extraído de la base de datos
            $resource_bd = findOneInArrayOfObjects($resources_bd, 'uid', $changeResourceStatus['uid']);

            if (!$resource_bd) {
                return response()->json(['message' => 'Uno de los recursos no existe'], 406);
            }

            // Sacamos el estado que le corresponde al recurso del array de la base de datos
            $status_bd = findOneInArrayOfObjects($statuses_resources, 'code', $changeResourceStatus['status']);

            if (!$status_bd) {
                return response()->json(['message' => 'El estado es incorrecto'], 406);
            }

            // Añadimos el recurso con el nuevo estado al array
            $updated_resources_data[] = [
                'uid' => $resource_bd->uid,
                'resource_status_uid' => $status_bd->uid,
                'reason' => $changeResourceStatus->reason ?? null
            ];

            // Si el recurso está publicado, lo añadimos al array para enviarlo a la api de búsqueda
            if($status_bd["code"] == "PUBLISHED") {
                $tags = $resource_bd->tags->pluck('tag')->toArray();
                $data_search_api[] = [
                    "uid" => $resource_bd->uid,
                    "title" => $resource_bd->title,
                    "description" => $resource_bd->description ?? "",
                    "tags" => $tags
                ];
            }
        }

        return [$updated_resources_data, $data_search_api];
    }

    private function updateDatabase($updated_resources_data, $data_search_api)
    {
        DB::transaction(function () use ($updated_resources_data, $data_search_api) {
            foreach ($updated_resources_data as $data) {
                EducationalResourcesModel::updateOrInsert(
                    ['uid' => $data['uid']],
                    [
                        'status_uid' => $data['resource_status_uid'],
                        'status_reason' => $data['reason']
                    ]
                );
            }

            if(env('ENABLED_API_SEARCH')) {
                $this->sendResourcesToApiSearch($data_search_api);
            }

            LogsController::createLog("Cambio de estado de recursos educativos", 'Recursos educativos', auth()->user()->uid);
        });
    }

    private function sendResourcesToApiSearch($data) {
        $endpoint = env('API_SEARCH_URL') . '/submit_learning_objects';
        $headers = [
            'API-KEY' => env('API_SEARCH_KEY'),
        ];

        guzzle_call($endpoint, $data, $headers, 'POST');
    }
}
