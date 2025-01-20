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
                "submenuselected" => "centres",
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
            $query->where('name', 'ILIKE', "%{$search}%");
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
     * @param  string $centerUid El UID del centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCenter($centerUid)
    {
        $center = CentersModel::where('uid', $centerUid)->first()->toArray();
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
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $centerUid = $request->input("center_uid");

        return DB::transaction(function () use ($request, $centerUid) {

            if (!$centerUid) {
                $center = new CentersModel();
                $centerUid = generateUuid();
                $center->uid = $centerUid;
                $isNew = true;
            } else {
                $center = CentersModel::find($centerUid);
                $isNew = false;
            }

            $center->fill($request->only([
                'name',
            ]));

            $center->save();

            LogsController::createLog('Añadir centro: ' . $center->name, 'Centros', auth()->user()->uid);

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

        $centers = CentersModel::whereIn('uid', $uids)->get();

        DB::transaction(function () use ($centers) {
            foreach ($centers as $center) {
                $center->delete();
                LogsController::createLog('Eliminar centro: ' . $center->name, 'Centros', auth()->user()->uid);
            }
        }, 5);

        return response()->json(['message' => 'Centros eliminados correctamente']);
    }
}
