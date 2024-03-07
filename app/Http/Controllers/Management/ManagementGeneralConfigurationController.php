<?php

namespace App\Http\Controllers\Management;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\UsersModel;
use App\Models\AutomaticResourceAprovalUsersModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Http\Request;

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

        $uids_teachers_automatic_aproval_resources = AutomaticResourceAprovalUsersModel::pluck('user_uid')->toArray();

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
                "uids_teachers_automatic_aproval_resources" => $uids_teachers_automatic_aproval_resources
            ]
        );
    }

    public function saveGeneralOptions(Request $request)
    {

        $updateData = [
            'necessary_approval_courses' => $request->input('necessary_approval_courses'),
            'necessary_approval_resources' => $request->input('necessary_approval_resources'),
            'course_status_change_notifications' => $request->input('course_status_change_notifications'),
            'necessary_approval_editions' => $request->input('necessary_approval_editions'),
        ];

        foreach ($updateData as $key => $value) {
            GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
        }

        return response()->json(['message' => 'Datos guardados correctamente']);
    }

    public function saveTeachersAutomaticAproval(Request $request)
    {
        $uidUsers = $request->input('selectedTeachers');

        // Primero eliminamos los UIDs que no están en la lista enviada
        AutomaticResourceAprovalUsersModel::whereNotIn('user_uid', $uidUsers)->delete();

        // Luego insertamos o actualizamos los que sí están
        foreach ($uidUsers as $uidUser) {
            AutomaticResourceAprovalUsersModel::updateOrInsert(
                ['user_uid' => $uidUser],
                ['uid' => generate_uuid()]
            );
        }

        return response()->json(['message' => 'Profesores guardados correctamente']);
    }

}
