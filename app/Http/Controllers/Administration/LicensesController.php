<?php

namespace App\Http\Controllers\Administration;

use App\Models\LicenseTypesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\EducationalResourcesModel;

class LicensesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return view(
            'administration.licenses.index',
            [
                "coloris" => true,
                "page_name" => "Licencias",
                "page_title" => "Licencias",
                "resources" => [
                    "resources/js/administration_module/licenses.js"
                ],
                "tabulator" => true,
                "submenuselected" => "licenses",
            ]
        );
    }

    /**
     * Obtiene todas las licencias.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLicenses(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = LicenseTypesModel::query();

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
     * Obtiene una licencia específico basada en su UID.
     *
     * @param  string $centerUid El UID del centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLicense($licenseUid)
    {
        $license = LicenseTypesModel::where('uid', $licenseUid)->first()->toArray();
        return response()->json($license);
    }

    /**
     * Crea una nueva licencia.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva licencia.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveLicense(Request $request)
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

        $licenseUid = $request->input("license_uid");

        return DB::transaction(function () use ($request, $licenseUid) {

            if (!$licenseUid) {
                $license = new LicenseTypesModel();
                $licenseUid = generateUuid();
                $license->uid = $licenseUid;
                $isNew = true;
            } else {
                $license = LicenseTypesModel::find($licenseUid);
                $isNew = false;
            }

            $license->fill($request->only([
                'name',
            ]));

            $license->save();

            LogsController::createLog('Añadir licencia: ' . $license->name, 'Licencias', auth()->user()->uid);

            return response()->json(['message' => $isNew ? 'Licencia añadida correctamente' : 'Licencia actualizada correctamente']);
        }, 5);
    }

    /**
     * Elimina un centro específico.
     *
     * @param  string $uids Array de uids de sistemas centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLicenses(Request $request)
    {
        $uids = $request->input('uids');

        // Comprobamos si hay cursos que estén vinculados a alguno de los centros
        $existsEducationalResources = EducationalResourcesModel::whereIn('license_type_uid', $uids)->exists();

        if ($existsEducationalResources) {
            return response()->json(['message' => 'No se pueden eliminar las licencias porque hay recursos educativos vinculados a ellos'], 406);
        }

        $licenses = LicenseTypesModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($licenses) {
            foreach($licenses as $license) {
                $license->delete();
                LogsController::createLog('Eliminar licencia: ' . $license->name, 'Licencias', auth()->user()->uid);
            }
        }, 5);

        return response()->json(['message' => 'Licencias eliminadas correctamente']);
    }

}
