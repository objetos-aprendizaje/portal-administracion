<?php

namespace App\Http\Controllers\Cataloging;

use App\Exceptions\OperationFailedException;
use App\Models\EducationalResourcesModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EducationalResourceTypesModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class EducationalResourceTypesController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$this->checkManagementAccess()) {
                abort(403);
            }
            return $next($request);
        })->except('index');
    }

    public function index()
    {

        if (!$this->checkManagementAccess()) {
            return view('access_not_allowed', [
                'title' => 'No tienes permiso para administrar los tipos de recurso educativo',
                'description' => 'El administrador ha bloqueado la administración de tipos de recurso educativo a los gestores.'
            ]);
        }

        $educationalResourceTypes = EducationalResourceTypesModel::get()->toArray();

        return view(
            'cataloging.educational_resource_types.index',
            [
                "page_name" => "Tipos de recursos educativos",
                "page_title" => "Tipos de recursos educativos",
                "resources" => [
                    "resources/js/cataloging_module/educational_resource_types.js"
                ],
                "educational_resource_types" => $educationalResourceTypes,
                "tabulator" => true,
                "submenuselected" => "cataloging-educational-resources",
            ]
        );
    }

    /**
     * Obtiene todas los tipos de recurso educativo.
     */
    public function getEducationalResourceTypes(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = EducationalResourceTypesModel::query();

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
     * Obtiene un tipo de recurso educativo por uid
     */
    public function getEducationalResourceType($educationalResourceTypeUid)
    {
        $educationalResourceType = EducationalResourceTypesModel::where('uid', $educationalResourceTypeUid)->first();

        if (!$educationalResourceType) {
            return response()->json(['message' => 'El tipo de recurso educativo no existe'], 406);
        }

        return response()->json($educationalResourceType, 200);
    }

    /**
     * Guarda una tipo de recurso educativo. Si recibe un uid, actualiza el tipo de recurso educativo con ese uid.
     */
    public function saveEducationalResourceType(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre no puede tener menos de 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'El nombre del tipo ya está en uso.',
            'educational_resource_type_uid.exists' => 'El tipo de recurso educativo no exite.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'min:3',
                'max:255',
                Rule::unique('educational_resource_types', 'name')->ignore($request->get('educational_resource_type_uid'), 'uid'),
            ],
            'description' => 'nullable',
            'educational_resource_type_uid' => 'nullable|exists:educational_resource_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $educationalResourceTypeUid = $request->get('educational_resource_type_uid');

        $name = $request->get('name');
        $description = $request->get('description');

        if ($educationalResourceTypeUid) {
            $educationalResourceType = EducationalResourceTypesModel::find($educationalResourceTypeUid);
            $isNew = false;
        } else {
            $educationalResourceType = new EducationalResourceTypesModel();
            $educationalResourceType->uid = generateUuid();
            $isNew = true;
        }

        $educationalResourceType->name = $name;
        $educationalResourceType->description = $description;

        DB::transaction(function () use ($educationalResourceType) {
            $educationalResourceType->save();
            LogsController::createLog('Guardar tipo de recurso educativo: ' . $educationalResourceType->name, 'Tipos de recursos educativos', auth()->user()->uid);
        });

        // Obtenemos todas los tipos
        $educationalResourceTypes = EducationalResourceTypesModel::get()->toArray();

        return response()->json([
            'message' => ($isNew) ? 'Tipo de recurso educativo añadido correctamente' : 'Tipo de recurso educativo actualizado correctamente',
            'educational_resource_types' => $educationalResourceTypes
        ], 200);
    }

    public function deleteEducationalResourceTypes(Request $request)
    {

        $uids = $request->input('uids');

        $existsEducationalResources = EducationalResourcesModel::whereIn("educational_resource_type_uid", $uids)->exists();

        if ($existsEducationalResources) {
            throw new OperationFailedException('No se pueden eliminar los tipos seleccionados porque están siendo utilizados por recursos educativos', 406);
        }

        $educationalResourceTypes = EducationalResourceTypesModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($educationalResourceTypes) {
            foreach ($educationalResourceTypes as $educationalResourceType) {
                $educationalResourceType->delete();
                LogsController::createLog('Eliminar tipo de recurso educativo: ' . $educationalResourceType->name, 'Tipos de recursos educativos', auth()->user()->uid);
            }
        });

        $educationalResourceTypes = EducationalResourceTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de recurso educativo eliminados correctamente', 'educational_resource_types' => $educationalResourceTypes], 200);
    }

    // Verifica si en caso de que el usuario sea sólo gestor, si tiene permisos
    private function checkManagementAccess()
    {
        $user = Auth::user();

        $rolesUser = $user->roles->pluck('code')->toArray();

        $generalOptions = app('general_options');

        // Aplicable si sólo tiene el rol de gestor
        if (empty(array_diff($rolesUser, ['MANAGEMENT'])) && !$generalOptions['managers_can_manage_educational_resources_types']) {
            return false;
        }

        return true;
    }
}
