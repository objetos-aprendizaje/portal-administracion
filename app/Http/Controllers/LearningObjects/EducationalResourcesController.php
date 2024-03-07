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
        $query = EducationalResourcesModel::with(["status", "type"])
            ->join('educational_resource_statuses as status', 'educational_resources.status_uid', '=', 'status.uid')
            ->join("educational_resource_types as type", "educational_resources.educational_resource_type_uid", "=", "type.uid");

        $sort = $request->get('sort');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $query->select("educational_resources.*");

        $data = $query->paginate($size);


        return response()->json($data, 200);
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

        $messages = [
            'name.required' => 'El nombre del recurso es obligatorio',
            'educational_resource_type_uid.required' => 'El tipo de recurso es obligatorio',
            'resource_way' => 'La forma del recurso es obligatoria',
            'resource_input_file.required' => 'Debes adjuntar un fichero',
            'resource_url.required' => 'Debes especificar la URL del recurso',
            'resource_url.url' => 'La URL no es válida'
        ];

        $resource_way = $request->resource_way;

        $rules = [
            'name' => 'required|string',
            'educational_resource_type_uid' => 'required|string',
            'resource_way' => 'required|string',
        ];

        $uid_resource = $request->educational_resource_uid;

        $isNew = !$uid_resource;
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

        if (!empty($errorsValidators)) {
            return response()->json(['errors' => $errorsValidators], 422);
        }

        if ($isNew) {
            $resource = new EducationalResourcesModel();
            $resource->uid = generate_uuid();
            $resource->creator_user_uid = auth()->user()->uid;
        } else {
            $resource = EducationalResourcesModel::where('uid', $uid_resource)->with('categories')->first();
        }

        // Comprobamos el nuevo estado que le corresponde al curso.
        $action = $request->input('action');

        $pending_approval = EducationalResourceStatusesModel::where('code', 'PENDING_APPROVAL')->first();
        $introduction = EducationalResourceStatusesModel::where('code', 'INTRODUCTION')->first();

        if (!$isNew) {
            $actual_status_course = $resource->status['code'];
            if(!in_array($actual_status_course, ['INTRODUCTION', 'UNDER_CORRECTION_APPROVAL'])) abort(403);

            switch ($actual_status_course) {
                case 'UNDER_CORRECTION_APPROVAL':
                    $new_resource_status = $pending_approval;
                    break;
                case 'INTRODUCTION':
                    $new_resource_status = $action == "submit" ? $pending_approval : null;
                    break;
                default:
                    $new_resource_status = $introduction;
                    break;
            }

        } else {
            $new_resource_status = $action == "submit" ? $pending_approval : $introduction;
        }

        if($new_resource_status) $resource->status_uid = $new_resource_status->uid;

        return DB::transaction(function () use ($request, $resource) {
            //Fichero de imagen
            $resource_image_input_file = $request->file('resource_image_input_file');
            if ($resource_image_input_file) {
                $resource->image_path = saveFile($resource_image_input_file, "attachments/resources", null, true);
            }

            $resource->fill($request->only([
                "name", "description", "educational_resource_type_uid", "license_type", "resource_way"
            ]));

            // Si es FILE, ponemos a null el campo de URL. Si es URL, ponemos a null el campo FILE
            if ($request->resource_way == 'FILE') {
                $resource_input_file = $request->file('resource_input_file');

                if ($resource_input_file) $resource->resource_path = saveFile($resource_input_file, "attachments/resources", null, true);

                $resource->resource_url = null;
            } elseif ($request->resource_way == 'URL') {
                $resource->resource_url = $request->resource_url;
                $resource->resource_path = null;
            }

            $resource->save();

            // Obtener los tags desde el frontend y decodificar el JSON a un array
            $tags = $request->input('tags');
            $tags = json_decode($tags, true);

            // Verificar si hay tags
            if (!empty($tags)) {
                // Obtener los tags actuales del curso desde la BD
                $current_tags = EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->pluck('tag')->toArray();

                // Identificar qué tags son nuevos y cuáles deben ser eliminados
                $tags_to_add = array_diff($tags, $current_tags);
                $tags_to_delete = array_diff($current_tags, $tags);

                // Eliminar los tags que ya no son necesarios
                EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->whereIn('tag', $tags_to_delete)->delete();

                // Preparar el array para la inserción masiva de nuevos tags
                $insertData = [];
                foreach ($tags_to_add as $tag) {
                    $insertData[] = [
                        'uid' => generate_uuid(),
                        'educational_resource_uid' => $resource->uid,
                        'tag' => $tag
                    ];
                }

                // Insertar todos los nuevos tags en una única operación de BD
                EducationalResourcesTagsModel::insert($insertData);
            } else {
                // Si no hay tags, eliminar todos los tags asociados a este curso
                EducationalResourcesTagsModel::where('educational_resource_uid', $resource->uid)->delete();
            }

            // metadata
            $metadata = $request->input('metadata');
            $metadata = json_decode($metadata, true);
            $resource->updateMetadata($metadata);

            // Categorías
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

            return response()->json(['message' => 'Recurso añadido correctamente']);
        }, 5);
    }

    public function deleteResources()
    {
        $resources_uids = request()->input('resourcesUids');

        EducationalResourcesModel::whereIn('uid', $resources_uids)->delete();
        return response()->json(['message' => 'Recursos eliminados correctamente']);
    }


    /**
     * Cambia el estado a un array de recursos
     */
    public function changeStatusesResources(Request $request)
    {

        $changesResourcesStatuses = $request->input('changesResourcesStatuses');

        if (!$changesResourcesStatuses) {
            return response()->json(['message' => 'No se han enviado los datos correctamente'], 406);
        }

        // Obtenemos los cursos de la base de datos
        $resources_bd = EducationalResourcesModel::whereIn('uid', array_column($changesResourcesStatuses, "uid"))->with('status')->get()->toArray();
        $statuses_resources = EducationalResourceStatusesModel::all()->toArray();

        // Aquí iremos almacenando los datos de los cursos que se van a actualizar
        $updated_resources_data = [];

        // Recorremos los cursos que nos vienen en el request y los comparamos con los de la base de datos
        foreach ($changesResourcesStatuses as $changeResourceStatus) {

            // Obtenemos el curso de la base de datos
            $resource_bd = findOneInArray($resources_bd, 'uid', $changeResourceStatus['uid']);

            // Si no existe el curso en la base de datos, devolvemos un error
            if (!$resource_bd) {
                return response()->json(['message' => 'Uno de los recursos no existe'], 406);
            }

            // Le cambiamos a cada curso el estado que nos viene en el request
            $status_bd = findOneInArray($statuses_resources, 'code', $changeResourceStatus['status']);

            if (!$status_bd) {
                return response()->json(['message' => 'El estado es incorrecto'], 406);
            }

            $updated_resources_data[] = [
                'uid' => $resource_bd['uid'],
                'resource_status_uid' => $status_bd['uid'],
                'reason' => $changeResourceStatus['reason'] ?? null
            ];
        }

        // Guardamos en la base de datos los cambios
        foreach ($updated_resources_data as $data) {
            EducationalResourcesModel::updateOrInsert(
                ['uid' => $data['uid']],
                [
                    'status_uid' => $data['resource_status_uid'],
                    'status_reason' => $data['reason']
                ]
            );
        }

        return response()->json(['message' => 'Se han actualizado los estados de los recursos correctamente'], 200);
    }
}
