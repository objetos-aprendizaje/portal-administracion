<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;

class ManagementPermissionsController extends BaseController {
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        return view(
            'administration.management_permissions',
            [
                "page_name" => "Permisos a gestores",
                "page_title" => "Permisos a gestores",
                "resources" => [
                    "resources/js/administration_module/management_permissions.js"
                ],
            ]
        );

    }

    public function saveManagersPermissionsForm(Request $request)
    {
        $updateData = [
            'managers_can_manage_categories' => $request->input('managers_can_manage_categories'),
            'managers_can_manage_course_types' => $request->input('managers_can_manage_course_types'),
            'managers_can_manage_educational_resources_types' => $request->input('managers_can_manage_educational_resources_types'),
            'managers_can_manage_calls' => $request->input('managers_can_manage_calls'),
        ];

        foreach ($updateData as $key => $value) {
            GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
        }

        // Procesar los datos y responder (puedes devolver JSON)
        return response()->json(['message' => 'Permisos guardados correctamente']);
    }
}
