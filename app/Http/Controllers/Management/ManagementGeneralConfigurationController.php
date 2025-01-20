<?php

namespace App\Http\Controllers\Management;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\UsersModel;
use App\Models\AutomaticResourceAprovalUsersModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class ManagementGeneralConfigurationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $teachers = UsersModel::with(['rol' => function ($query) {
            $query->where('code', 'TEACHER');
        }])->whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->get()->toArray();

        $uidsTeachersAutomaticAprovalResources = AutomaticResourceAprovalUsersModel::pluck('user_uid')->toArray();

        return view(
            'management.general_configuration.index',
            [
                "page_name" => "Configuración general",
                "page_title" => "Configuración general",
                "resources" => [
                    "resources/js/management_module/general_configuration.js"
                ],
                "tomselect" => true,
                "teachers" => $teachers,
                "uids_teachers_automatic_aproval_resources" => $uidsTeachersAutomaticAprovalResources,
                "submenuselected" => "management-general-configuration",
            ]
        );
    }

    public function saveGeneralOptions(Request $request)
    {

        $updateData = [
            'necessary_approval_resources' => $request->input('necessary_approval_resources'),
            'necessary_approval_editions' => $request->input('necessary_approval_editions'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Guardar opciones generales', 'Configuración general de gestión', auth()->user()->uid);
        });

        return response()->json(['message' => 'Datos guardados correctamente']);
    }

    public function saveTeachersAutomaticAproval(Request $request)
    {
        $uidsTeachers = $request->input('selectedTeachers');

        DB::transaction(function () use ($uidsTeachers) {
            // Primero eliminamos los UIDs que no están en la lista enviada
            AutomaticResourceAprovalUsersModel::whereNotIn('user_uid', $uidsTeachers)->delete();

            // Luego insertamos o actualizamos los que sí están
            foreach ($uidsTeachers as $uidTeacher) {
                AutomaticResourceAprovalUsersModel::firstOrCreate(
                    ['user_uid' => $uidTeacher],
                    ['uid' => generateUuid()]
                );
            }

            LogsController::createLog('Guardar profesores con aprobación automática', 'Configuración general de gestión', auth()->user()->uid);
        });

        return response()->json(['message' => 'Profesores guardados correctamente']);
    }
}
