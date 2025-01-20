<?php

namespace App\Http\Controllers\Cataloging;

use App\Exceptions\OperationFailedException;
use App\Models\CallsEducationalProgramTypesModel;
use App\Models\CoursesModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\RedirectionQueriesEducationalProgramTypesModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducationalProgramTypesController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $educationalProgramTypes = EducationalProgramTypesModel::get()->toArray();

        return view(
            'cataloging.educational_program_types.index',
            [
                "page_name" => "Tipos de programas formativos",
                "page_title" => "Tipos de programas formativos",
                "resources" => [
                    "resources/js/cataloging_module/educational_program_types.js"
                ],
                "educational_program_types" => $educationalProgramTypes,
                "tabulator" => true,
                "submenuselected" => "cataloging-educational-program-types",
            ]
        );
    }

    /**
     * Obtiene todas los tipos de programa educativo.
     */
    public function getEducationalProgramTypes(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = EducationalProgramTypesModel::query();

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
     * Obtiene un tipo de programa educativo por uid
     */
    public function getEducationalProgramType($educationalProgramTypeUid)
    {
        $educationalProgramType = EducationalProgramTypesModel::where('uid', $educationalProgramTypeUid)->first();

        if (!$educationalProgramType) {
            return response()->json(['message' => 'El tipo de programa formativo no existe'], 406);
        }

        return response()->json($educationalProgramType, 200);
    }

    /**
     * Guarda un tipo de programa educativo. Si recibe un uid, actualiza el tipo de programa educativo con ese uid.
     */
    public function saveEducationalProgramType(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre no puede tener menos de 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'El nombre del tipo ya está en uso.',
            'educational_program_type_uid.exists' => 'El tipo de programa formativo no exite.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'min:3',
                'max:255',
                Rule::unique('educational_program_types', 'name')->ignore($request->get('educational_program_type_uid'), 'uid'),
            ],
            'description' => 'nullable',
            'educational_program_type_uid' => 'nullable|exists:educational_program_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $educationalProgramTypeUid = $request->get('educational_program_type_uid');

        if ($educationalProgramTypeUid) {
            $educationalProgramType = EducationalProgramTypesModel::find($educationalProgramTypeUid);
            $isNew = false;
        } else {
            $educationalProgramType = new EducationalProgramTypesModel();
            $educationalProgramType->uid = generateUuid();
            $isNew = true;
        }


        $managersCanEmitCredentials = intval($request->get('managers_can_emit_credentials'));
        $teachersCanEmitCredentials = intval($request->get('teachers_can_emit_credentials'));

        $educationalProgramType->fill([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'managers_can_emit_credentials' => $managersCanEmitCredentials,
            'teachers_can_emit_credentials' => $teachersCanEmitCredentials,
        ]);



        DB::transaction(function () use ($educationalProgramType, $isNew) {
            $educationalProgramType->save();

            $messageLog = $isNew ? 'Añadir tipo de programa formativo: ' : 'Actualizar tipo de programa formativo: ';
            $messageLog .= $educationalProgramType->name;
            LogsController::createLog($messageLog, 'Tipos de programas formativo', auth()->user()->uid);
        });

        // Obtenemos todas los tipos
        $educationalProgramTypes = EducationalProgramTypesModel::get()->toArray();

        return response()->json([
            'message' => ($isNew) ? 'Tipo de programa formativo añadido correctamente' : 'Tipo de programa formativo actualizado correctamente',
            'educational_program_types' => $educationalProgramTypes
        ], 200);
    }

    public function deleteEducationalProgramTypes(Request $request)
    {

        $uids = $request->input('uids');

        $this->checkExistence(EducationalProgramsModel::class, $uids, 'No se pueden eliminar los tipos de programa formativo porque están siendo utilizados en programas formativos');
        $this->checkExistence(CallsEducationalProgramTypesModel::class, $uids, 'No se pueden eliminar los tipos de programa formativo porque están siendo utilizados en convocatorias');

        $educationalProgramTypes = EducationalProgramTypesModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($educationalProgramTypes) {
            foreach ($educationalProgramTypes as $educationalProgramType) {
                $educationalProgramType->delete();
                LogsController::createLog('Eliminar tipo de programa formativo: ' . $educationalProgramType->name, 'Tipos de programas formativo', auth()->user()->uid);
            }
        });

        $educationalProgramTypes = EducationalProgramTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de programa formativo eliminados correctamente', 'educational_program_types' => $educationalProgramTypes], 200);
    }

    private function checkExistence($model, $uids, $errorMessage)
    {
        $exists = $model::whereIn('educational_program_type_uid', $uids)->exists();

        if ($exists) {
            throw new OperationFailedException($errorMessage, 406);
        }
    }
}
