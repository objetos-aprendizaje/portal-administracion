<?php

namespace App\Http\Controllers\Administration;

use App\Models\ApiKeysModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class ApiKeysController extends BaseController
{

    public function index()
    {

        return view(
            'administration.api_keys.index',
            [
                "page_name" => "Claves de API",
                "page_title" => "Claves de API",
                "resources" => [
                    "resources/js/administration_module/api_keys.js",
                ],
                "tabulator" => true,
                "submenuselected" => "api-keys",
            ]
        );
    }


    /**
     * @param {*} string $apiKeyUid UID de la clave API.
     * Obtiene el listado de claves API
     */
    public function getApiKeys(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = ApiKeysModel::query();

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('api_key', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }


    public function getApiKey($apiKeyUid)
    {

        $apiKey = ApiKeysModel::where('uid', $apiKeyUid)->first();

        return response()->json($apiKey, 200);
    }

    public function deleteApiKey(Request $request)
    {
        $apiKeysUids = $request->input('uids');

        $apiKeys = ApiKeysModel::whereIn('uid', $apiKeysUids)->get();

        DB::transaction(function () use ($apiKeys) {
            foreach ($apiKeys as $apiKey) {
                $apiKey->delete();
                LogsController::createLog('Eliminar clave API: ' . $apiKey->name, 'Claves API', auth()->user()->uid);
            }
        }, 5);

        return response()->json(['message' => 'Claves de API eliminadas correctamente']);
    }


    /**
     *
     * Obtiene el html del listado de redirecciones de consulta
     */
    public function saveApiKey(Request $request)
    {

        $messages = [
            'name.required' => 'El nombre es obligatorio',
            'api_key.required' => 'La clave de API es obligatoria',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'api_key' => 'required|max:200'
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Hay campos incorrectos', 'errors' => $validator->errors()], 422);
        }

        $apiKeyUid = $request->input('api_key_uid');

        if ($apiKeyUid) {
            $apiKeyQuery = ApiKeysModel::where('uid', $apiKeyUid)->first();
        } else {
            $apiKeyQuery = new ApiKeysModel();
            $apiKeyQuery->uid = generateUuid();
        }

        $apiKeyQuery->fill($request->only([
            'api_key_uid',
            'name',
            'api_key'
        ]));

        DB::transaction(function () use ($apiKeyQuery) {
            $apiKeyQuery->save();
            LogsController::createLog('Añadir clave API: ' . $apiKeyQuery->name, 'Claves APIs', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => 'Clave guardada correctamente']);
    }
}
