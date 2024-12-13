<?php

namespace App\Http\Controllers\Cataloging;

use App\Models\CategoriesModel;
use App\Models\CertificationTypesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\CoursesModel;

class CertificationTypesController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $certification_types = CertificationTypesModel::get()->toArray();

        $categories = CategoriesModel::whereNull('parent_category_uid')->with('subcategories')->get()->toArray();

        return view(
            'cataloging.certification_types.index',
            [
                "page_name" => "Tipos de certificación",
                "page_title" => "Tipos de certificación",
                "resources" => [
                    "resources/js/cataloging_module/certification_types.js"
                ],
                "certification_types" => $certification_types,
                "tabulator" => true,
                "categories" => $categories,
                "tomselect" => true,
                "choicesjs" => true,
                "submenuselected" => "cataloging-certification-types",
            ]
        );
    }

    /**
     * Obtiene todas los tipo de certificación.
     */
    public function getCertificationTypes(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CertificationTypesModel::query();

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
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
     * Obtiene un tipo de certificación por uid
     */
    public function getCertificationType($certification_type_uid)
    {

        // if (!$certification_type_uid) {
        //     return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        // }  Se ha quitado por error en pruebas unitaras ya que la misma ruta evalua si existe parametro o no

        $certification_type = CertificationTypesModel::where('uid', $certification_type_uid)->first();

        if (!$certification_type) {
            return response()->json(['message' => 'El tipo de certificación no existe'], 406);
        }

        return response()->json($certification_type, 200);
    }

    /**
     * Guarda una tipo de certificación. Si recibe un uid, actualiza el tipo de certificación con ese uid.
     */
    public function saveCertificationType(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre no puede tener menos de 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'El nombre del tipo ya está en uso.',
            'certification_type_uid.exists' => 'El tipo de certificación no exite.',
            'category_uid.required' => 'El campo categoría es obligatorio.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 'min:3', 'max:255',
                Rule::unique('certification_types', 'name')->ignore($request->get('certification_type_uid'), 'uid'),
            ],
            'description' => 'nullable',
            'certification_type_uid' => 'nullable|exists:certification_types,uid',
            'category_uid' => 'required'
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message'=> 'Hay errores en el formulario', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $certification_type_uid = $request->get('certification_type_uid');
        $name = $request->get('name');
        $description = $request->get('description');
        $category_uid = $request->get('category_uid');

        if ($certification_type_uid) {
            $certification_type = CertificationTypesModel::find($certification_type_uid);
            $isNew = false;
        } else {
            $certification_type = new CertificationTypesModel();
            $certification_type->uid = generate_uuid();
            $isNew = true;
        }


        $certification_type->name = $name;
        $certification_type->description = $description;
        $certification_type->category_uid = $category_uid;

        DB::transaction(function () use ($certification_type) {
            $certification_type->save();
            LogsController::createLog("Crear tipo de certificado: " . $certification_type->name, 'Tipos de certificados', auth()->user()->uid);
        });

        // Obtenemos todas los tipos
        $certification_types = CertificationTypesModel::get()->toArray();

        return response()->json([
            'message' => ($isNew) ? 'Tipo de certificación añadida correctamente' : 'Tipo de certificación actualizada correctamente',
            'certification_types' => $certification_types
        ], 200);
    }

    public function deleteCertificationTypes(Request $request)
    {

        $uids = $request->input('uids');

        // Comprobación si está vinculado a algún curso
        $existsInCourses = CoursesModel::whereIn('certification_type_uid', $uids)->exists();
        if ($existsInCourses) {
            return response()->json(['message' => 'No se pueden eliminar los tipos de certificación porque alguno está vinculado a cursos'], 406);
        }

        $certificationTypes = CertificationTypesModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($certificationTypes) {
            foreach($certificationTypes as $certificationType) {
                $certificationType->delete();
                LogsController::createLog("Eliminación de tipo de certificado: " . $certificationType->name, 'Tipos de certificados', auth()->user()->uid);
            }
        });

        $certification_types = CertificationTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de certificaciones eliminados correctamente', 'certification_types' => $certification_types], 200);
    }
}
