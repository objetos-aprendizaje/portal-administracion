<?php

namespace App\Http\Controllers\Administration;

use App\Models\LmsSystemsModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class LmsSystemsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return view(
            'administration.lms_systems.index',
            [
                "coloris" => true,
                "page_name" => "Sistemas LMS",
                "page_title" => "Sistemas LMS",
                "resources" => [
                    "resources/js/administration_module/lms_systems.js"
                ],
                "tabulator" => true,
                "submenuselected" => "lms-systems",
            ]
        );
    }

    /**
     * Obtiene todos los sistemas LMS.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLmsSystems(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = LmsSystemsModel::query();

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }


    /**
     * Obtiene un LMS específico basada en su UID.
     *
     * @param  string $lms_system_uid El UID del LMS.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLmsSystem($lms_system_uid)
    {
        $lms_system = LmsSystemsModel::where('uid', $lms_system_uid)->first()->toArray();
        return response()->json($lms_system);
    }

    /**
     * Crea un nuevo LMS.
     *
     * @param  \Illuminate\Http\Request  $request Los datos del nuevo LMS.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveLmsSystem(Request $request)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'identifier' => 'El identificador es obligatorio',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'identifier' => 'required',
        ], $messages);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lms_system_uid = $request->input("lms_system_uid");

        return DB::transaction(function () use ($request, $lms_system_uid) {

            if (!$lms_system_uid) {
                $lms_system = new LmsSystemsModel();
                $lms_system_uid = generate_uuid();
                $lms_system->uid = $lms_system_uid;
                $isNew = true;
            } else {
                $lms_system = LmsSystemsModel::find($lms_system_uid);
                $isNew = false;
            }

            $lms_system->fill($request->only([
                'name', 'identifier',
            ]));

            $lms_system->save();

            LogsController::createLog('Añadir sistema LMS', 'Sistemas LMS', auth()->user()->uid);

            return response()->json(['message' => $isNew ? 'LMS añadido correctamente' : 'LMS actualizado correctamente']);
        }, 5);
    }

    /**
     * Elimina un LMS específico.
     *
     * @param  string $uids Array de uids de sistemas LMS.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLmsSystems(Request $request)
    {
        $uids = $request->input('uids');

        DB::transaction(function () use ($uids) {
            LmsSystemsModel::destroy($uids);
            LogsController::createLog('Eliminar sistema LMS', 'Sistemas LMS', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => 'LMS eliminados correctamente']);
    }
}
