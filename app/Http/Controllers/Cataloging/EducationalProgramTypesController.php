<?php

namespace App\Http\Controllers\Cataloging;

use App\Models\EducationalProgramTypesModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducationalProgramTypesController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $educational_program_types = EducationalProgramTypesModel::get()->toArray();

        return view(
            'cataloging.educational_program_types.index',
            [
                "page_name" => "Tipos de programas educativos",
                "page_title" => "Tipos de programas educativos",
                "resources" => [
                    "resources/js/cataloging_module/educational_program_types.js"
                ],
                "educational_program_types" => $educational_program_types,
                "tabulator" => true
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
     * Obtiene un tipo de programa educativo por uid
     */
    public function getEducationalProgramType($educational_program_type_uid)
    {

        if (!$educational_program_type_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $educational_program_type = EducationalProgramTypesModel::where('uid', $educational_program_type_uid)->first();

        if (!$educational_program_type) {
            return response()->json(['message' => 'El tipo de programa educativo no existe'], 406);
        }

        return response()->json($educational_program_type, 200);
    }

    /**
     * Guarda un tipo de programa educativo. Si recibe un uid, actualiza el tipo de programa educativo con ese uid.
     */
    public function saveEducationalProgramType(Request $request)
    {

        //dd($request->all());

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre no puede tener menos de 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'El nombre del tipo ya está en uso.',
            'educational_program_type_uid.exists' => 'El tipo de programa educativo no exite.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 'min:3', 'max:255',
                Rule::unique('educational_program_types', 'name')->ignore($request->get('educational_program_type_uid'), 'uid'),
            ],
            'description' => 'nullable',
            'educational_program_type_uid' => 'nullable|exists:educational_program_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $educational_program_type_uid = $request->get('educational_program_type_uid');

        if ($educational_program_type_uid) {
            $educational_program_type = EducationalProgramTypesModel::find($educational_program_type_uid);
            $isNew = false;
        } else {
            $educational_program_type = new EducationalProgramTypesModel();
            $educational_program_type->uid = generate_uuid();
            $isNew = true;
        }

        $educational_program_type->fill([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'managers_can_emit_credentials' => $request->get('managers_can_emit_credentials') ? 1 : 0,
            'teachers_can_emit_credentials' => $request->get('teachers_can_emit_credentials') ? 1 : 0,
        ]);

        $educational_program_type->save();

        // Obtenemos todas los tipos
        $educational_program_types = EducationalProgramTypesModel::get()->toArray();

        return response()->json([
            'message' => ($isNew) ? 'Tipo de programa educativo añadido correctamente' : 'Tipo de programa educativo actualizado correctamente',
            'educational_program_types' => $educational_program_types
        ], 200);
    }

    public function deleteEducationalProgramTypes(Request $request)
    {

        $uids = $request->input('uids');

        EducationalProgramTypesModel::destroy($uids);

        $educational_program_types = EducationalProgramTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de programa educativo eliminados correctamente', 'educational_program_types' => $educational_program_types], 200);
    }
}
