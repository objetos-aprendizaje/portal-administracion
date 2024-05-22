<?php

namespace App\Http\Controllers\Administration;

use App\Models\CentersModel;
use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class CentersController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return view(
            'administration.centers.index',
            [
                "coloris" => true,
                "page_name" => "Centros",
                "page_title" => "Centros",
                "resources" => [
                    "resources/js/administration_module/centers.js"
                ],
                "tabulator" => true,
            ]
        );
    }

    /**
     * Obtiene todos los sistemas centros.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCenters(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CentersModel::query();

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
     * Obtiene un centro específico basada en su UID.
     *
     * @param  string $center_uid El UID del centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCenter($center_uid)
    {
        $center = CentersModel::where('uid', $center_uid)->first()->toArray();
        return response()->json($center);
    }

    /**
     * Crea un nuevo centro.
     *
     * @param  \Illuminate\Http\Request  $request Los datos del nuevo centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCenter(Request $request)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], $messages);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $center_uid = $request->input("center_uid");

        return DB::transaction(function () use ($request, $center_uid) {

            if (!$center_uid) {
                $center = new CentersModel();
                $center_uid = generate_uuid();
                $center->uid = $center_uid;
                $isNew = true;
            } else {
                $center = CentersModel::find($center_uid);
                $isNew = false;
            }

            $center->fill($request->only([
                'name',
            ]));

            $center->save();

            LogsController::createLog('Añadir centro', 'Centros', auth()->user()->uid);

            return response()->json(['message' => $isNew ? 'Centro añadido correctamente' : 'Centro actualizado correctamente']);
        }, 5);
    }

    /**
     * Elimina un centro específico.
     *
     * @param  string $uids Array de uids de sistemas centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCenters(Request $request)
    {
        $uids = $request->input('uids');

        // Comprobamos si hay cursos que estén vinculados a alguno de los centros
        $existsCourses = CoursesModel::whereIn('center_uid', $uids)->exists();

        if ($existsCourses) {
            return response()->json(['message' => 'No se pueden eliminar los centros porque hay cursos vinculados a ellos'], 406);
        }

        DB::transaction(function () use ($uids) {
            CentersModel::destroy($uids);
            LogsController::createLog('Eliminar centro', 'Centros', auth()->user()->uid);
        }, 5);

        return response()->json(['message' => 'Centros eliminados correctamente']);
    }
}
