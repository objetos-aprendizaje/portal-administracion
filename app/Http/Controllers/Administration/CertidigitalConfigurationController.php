<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Logs\LogsController;
use App\Models\GeneralOptionsModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class CertidigitalConfigurationController extends BaseController {

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        return view(
            'administration.certidigital',
            [
                "page_name" => "Configuración de Certidigital",
                "page_title" => "Configuración de Certidigital",
                "resources" => [
                    "resources/js/administration_module/certidigital_configuration.js"
                ],
                'submenuselected' => 'certidigital',
            ]
        );

    }

    public function saveCertidigitalForm(Request $request) {
        $errorMessages = $this->validateCertidigitalForm($request);

        if($errorMessages->any()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errorMessages], 422);
        }

        $updateData = [
            'certidigital_url' => $request->input('certidigital_url'),
            'certidigital_client_id' => $request->input('certidigital_client_id'),
            'certidigital_client_secret' => $request->input('certidigital_client_secret'),
            'certidigital_username' => $request->input('certidigital_username'),
            'certidigital_password' => $request->input('certidigital_password'),
            'certidigital_url_token' => $request->input('certidigital_url_token'),
            'certidigital_center_id' => $request->input('certidigital_center_id'),
            'certidigital_organization_oid' => $request->input('certidigital_organization_oid'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización datos de certidigital', 'Certidigital', auth()->user()->uid);
        });

        return response()->json(['message' => 'Configuración de certidigital correctamente']);
    }

    private function validateCertidigitalForm($request) {
        $messages = [
            'certidigital_url.required' => 'La URL es obligatoria',
            'certidigital_client_id.required' => 'El cliente es obligatorio',
            'certidigital_client_secret.required' => 'El secreto del cliente es obligatorio',
            'certidigital_username.required' => 'El nombre de usuario es obligatorio',
            'certidigital_password.required' => 'La contraseña es obligatoria',
        ];

        $rules = [
            'certidigital_url' => 'required',
            'certidigital_client_id' => 'required',
            'certidigital_client_secret' => 'required',
            'certidigital_username' => 'required',
            'certidigital_password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $errorMessages = $validator->errors();

        return $errorMessages;
    }
}
