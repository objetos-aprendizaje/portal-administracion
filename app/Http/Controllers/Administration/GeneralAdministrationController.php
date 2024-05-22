<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Logs\LogsController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GeneralAdministrationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {


        return view(
            'administration.general',
            [
                "coloris" => true,
                "page_name" => "Configuración general",
                "page_title" => "Configuración general",
                "resources" => [
                    "resources/js/administration_module/general.js"
                ],
            ]
        );
    }

    public function saveSMTPEmailForm(Request $request)
    {

        $updateData = [
            'smtp_server' => $request->input('server'),
            'smtp_port' => $request->input('port'),
            'smtp_user' => $request->input('username'),
            'smtp_password' => $request->input('password'),
            'smtp_name_from' => $request->input('smtp_name_from'),
        ];

        $allNull = collect($updateData)->every(function ($value) {
            return is_null($value);
        });

        $allFilled = collect($updateData)->every(function ($value) {
            return !is_null($value) && $value !== '';
        });

        if (!($allNull || $allFilled)) {
            return response()->json(['message' => 'Todos los campos deben estar vacíos o todos deben estar rellenos.'], 400);
        }

        $configEmailService = [];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
                $configEmailService[$key] = $value;
            }

            LogsController::createLog('Actualización del servidor de correo', 'Configuración general', auth()->user()->uid);
        });

        Cache::put('parameters_email_service', $configEmailService, 60 * 24); // Cache for 24 hours

        // Procesar los datos y responder (puedes devolver JSON)
        return response()->json(['message' => 'Servidor de correo guardado correctamente']);
    }

    public function saveLogoImage(Request $request)
    {
        if (!isset($_FILES["logoPoaFile"])) return response()->json(['message' => env('ERROR_MESSAGE'), 400]);

        $loga_poa_image = $request->file('logoPoaFile');
        $targetFile = saveFile($loga_poa_image, "images/custom-logos", null, true);

        if (!$targetFile) {
            return response()->json(['message' => 'Error al guardar la imagen', 405]);
        }

        DB::transaction(function () use ($targetFile) {
            GeneralOptionsModel::where('option_name', 'poa_logo')->update(['option_value' => $targetFile]);
            LogsController::createLog('Actualización del logo', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['message' => 'Logo actualizado correctamente', 'route' => $targetFile]);
    }

    public function restoreLogoImage()
    {
        DB::transaction(function () {
            GeneralOptionsModel::where('option_name', 'poa_logo')->update(['option_value' => null]);
            LogsController::createLog('Eliminación del logo', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['message' => 'Logo restaurado correctamente']);
    }

    public function changeColors(Request $request)
    {
        DB::transaction(function () use ($request) {
            $updateData = [
                'color_1' => $request->input('color1'),
                'color_2' => $request->input('color2'),
                'color_3' => $request->input('color3'),
                'color_4' => $request->input('color4'),
            ];

            foreach ($updateData as $color) {
                if (!validateHexadecimalColor($color)) return response()->json(['success' => false, 'message' => 'Hay algún color que no es válido'], 400);
            }

            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de la paleta de colores', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['success' => true, 'message' => 'Colores guardados correctamente']);
    }

    public function saveUniversityInfo(Request $request)
    {

        DB::transaction(function () use ($request) {

            $updateData = [
                'company_name' => $request->input('company_name'),
                'commercial_name' => $request->input('commercial_name'),
                'cif' => $request->input('cif'),
                'fiscal_domicile' => $request->input('fiscal_domicile'),
                'work_center_address' => $request->input('work_center_address'),
            ];

            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de la información de la universidad', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['message' => 'Datos guardados correctamente']);
    }

    public function saveGeneralOptions(Request $request)
    {
        DB::transaction(function () use ($request) {
            $updateData = [
                'learning_objects_appraisals' => $request->input('learning_objects_appraisals'),
                'payment_gateway' => $request->input('payment_gateway'),
                'operation_by_calls' => $request->input('operation_by_calls'),
            ];

            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización configuración general', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['message' => 'Opciones guardadas correctamente']);
    }

    public function saveScripts(Request $request)
    {

        $scripts = $request->input("scripts");

        DB::transaction(function () use ($scripts) {
            GeneralOptionsModel::where('option_name', "scripts")->update(['option_value' => $scripts]);
            LogsController::createLog('Actualización scripts', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['message' => 'Scripts guardados correctamente']);
    }

    public function saveRrss(Request $request)
    {
        $updateData = [
            'facebook_url' => $request->input('facebook_url'),
            'x_url' => $request->input('x_url'),
            'youtube_url' => $request->input('youtube_url'),
            'instagram_url' => $request->input('instagram_url'),
            'telegram_url' => $request->input('telegram_url'),
            'linkedin_url' => $request->input('linkedin_url'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
                LogsController::createLog('Actualización redes sociales', 'Configuración general', auth()->user()->uid);
            }
        });


        return response()->json(['message' => 'Redes sociales guardadas correctamente']);
    }

    public function saveCarrousel(Request $request)
    {

        $messages = [
            'carrousel_title.required' => 'El título del carrousel es obligatorio',
            'carrousel_description.required' => 'La descripción del carrousel es obligatoria',
        ];

        $rules = [
            'carrousel_title' => 'required',
            'carrousel_description' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $updateData = [
            'carrousel_title' => $request->input('carrousel_title'),
            'carrousel_description' => $request->input('carrousel_description'),
        ];

        $carrousel_image_input_file = $request->file('carrousel_image_input_file');

        if ($carrousel_image_input_file) {
            $updateData['carrousel_image_path'] = saveFile($carrousel_image_input_file, "images/carrousel-default-images", null, true);
        }

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización carrousel por defecto', 'Configuración general', auth()->user()->uid);
        });


        return response()->json(['message' => 'Opciones de carrousel guardadas correctamente']);
    }

    public function addFont(Request $request)
    {
        $fontFile = $request->file('fontFile');
        $fontKey = $request->input('fontKey');
        $fontPath = saveFile($fontFile, "fonts", $fontKey, true);

        if(!$fontPath) return response()->json(['message' => 'Error al guardar la fuente', 405]);

        DB::transaction(function () use ($fontPath, $fontKey) {
            GeneralOptionsModel::where('option_name', $fontKey)->update(['option_value' => $fontPath]);
            LogsController::createLog('Añadir tipografía', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['fontPath' => $fontPath, 'message' => 'Fuente guardada correctamente']);

    }

    public function deleteFont(Request $request)
    {
        $fontKey = $request->input('fontKey');
        $fontPath = GeneralOptionsModel::where('option_name', $fontKey)->first()->option_value;

        deleteFile($fontPath);

        DB::transaction(function () use ($fontKey) {
            GeneralOptionsModel::where('option_name', $fontKey)->update(['option_value' => null]);
            LogsController::createLog('Eliminar tipografía', 'Configuración general', auth()->user()->uid);
        });

        return response()->json(['message' => 'Fuente eliminada correctamente']);
    }
}
