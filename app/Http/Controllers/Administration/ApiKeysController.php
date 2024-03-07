<?php

namespace App\Http\Controllers\Administration;

use App\Models\ApiKeysModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            ]
        );
    }


    /**
     * @param {*} string $api_key_uid UID de la clave API.
     * Obtiene el listado de claves API
     */
    public function getApiKeys(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = ApiKeysModel::query();

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('api_key', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }


    public function getApiKey($api_key_uid)
    {

        $api_key = ApiKeysModel::where('uid', $api_key_uid)->first();

        return response()->json($api_key, 200);
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

        $api_key_uid = $request->input('api_key_uid');

        if ($api_key_uid) {
            $api_key_query = ApiKeysModel::where('uid', $api_key_uid)->first();
        } else {
            $api_key_query = new ApiKeysModel();
            $api_key_query->uid = generate_uuid();
        }

        $api_key_query->fill($request->only([
            'api_key_uid', 'name', 'api_key'
        ]));

        $api_key_query->save();

        return response()->json(['message' => 'Clave guardada correctamente']);
    }
}
