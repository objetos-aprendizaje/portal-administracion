<?php

namespace App\Http\Controllers;

use App\Models\NotificationsTypesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\DepartmentsModel;
use App\Rules\NifNie;
use Illuminate\Support\Facades\Validator;

class MyProfileController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $notificationTypes = NotificationsTypesModel::all();

        $user = Auth::user();
        $rolesUser = Auth::user()->roles;
        $automaticNotificationTypes = AutomaticNotificationTypesModel::with('roles')
            ->whereHas('roles', function ($query) use ($rolesUser) {
                $query->whereIn('uid', $rolesUser->pluck("uid"));
            })
            ->get();

        $departments = DepartmentsModel::all();

        $userGeneralNotificationsDisabled = $user->generalNotificationsTypesDisabled()->get()->toArray();
        $userEmailNotificationsDisabled = $user->emailNotificationsTypesDisabled()->get()->toArray();

        $userAutomaticGeneralNotificationsDisabled = $user->automaticGeneralNotificationsTypesDisabled()->get()->toArray();
        $userAutomaticEmailNotificationsDisabled = $user->automaticEmailNotificationsTypesDisabled()->get()->toArray();

        return view(
            'my_profile.index',
            [
                "coloris" => true,
                "page_name" => "Mi perfil",
                "page_title" => "Mi perfil",
                "resources" => [
                    "resources/js/my_profile.js"
                ],
                'notification_types' => $notificationTypes,
                "user" => $user,
                "submenuselected" => "my_profile",
                "userGeneralNotificationsDisabled" => $userGeneralNotificationsDisabled,
                "userEmailNotificationsDisabled" => $userEmailNotificationsDisabled,
                "automaticNotificationTypes" => $automaticNotificationTypes,
                "userAutomaticGeneralNotificationsDisabled" => $userAutomaticGeneralNotificationsDisabled,
                "userAutomaticEmailNotificationsDisabled" => $userAutomaticEmailNotificationsDisabled,
                "departments" => $departments
            ]
        );
    }

    public function updateUser(Request $request)
    {
        $user = Auth::user();

        $messages = [
            'first_name.required' => 'El campo Nombre es obligatorio',
            'last_name.required' => 'El campo Apellidos es obligatorio',
            'nif.required' => 'El campo NIF es obligatorio',
            'nif.max' => 'El campo NIF no puede tener más de 9 caracteres',
        ];

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'nif' => ['required', 'max:9', new NifNie],
            'photo_path' => 'max:6144'
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->nif = $request->input('nif');
        $user->curriculum = $request->input('curriculum');
        $user->general_notifications_allowed = $request->input('general_notifications_allowed');
        $user->email_notifications_allowed = $request->input('email_notifications_allowed');
        $user->department_uid = $request->input('department_uid');

        DB::transaction(function () use ($user, $request) {

            $this->syncGeneralNotificationTypes($request);
            $this->syncEmailNotificationTypes($request);

            $this->syncAutomaticGeneralNotificationTypes($request);
            $this->syncAutomaticEmailNotificationTypes($request);

            if ($request->file('photo_path')) {
                $file = $request->file('photo_path');
                $path = 'images/users-images';
                $destinationPath = public_path($path);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $timestamp = time();

                $filename = "{$originalName}-{$timestamp}.{$extension}";

                $file->move($destinationPath, $filename);

                $user->photo_path = $path . "/" . $filename;
            }

            $user->save();
            LogsController::createLog("Actualización de perfil", 'Mi perfil', auth()->user()->uid);
        });

        return response()->json(['message' => 'Tu perfil se ha actualizado correctamente'], 200);
    }

    public function deletePhoto() {
        $user = Auth::user();
        $user->photo_path = null;
        $user->save();

        return response()->json(['message' => 'La foto de perfil se ha eliminado correctamente'], 200);
    }

    private function syncGeneralNotificationTypes(Request $request)
    {
        $generalNotificationTypesDisabled = $request->input('general_notification_types_disabled');
        $generalNotificationTypesDisabled = json_decode($generalNotificationTypesDisabled, true);
        $generalNotificationTypesSync = $this->prepareNotificationTypesSync($generalNotificationTypesDisabled);

        auth()->user()->generalNotificationsTypesDisabled()->sync($generalNotificationTypesSync);
    }

    private function syncEmailNotificationTypes(Request $request)
    {
        $emailNotificationTypesDisabled = $request->input('email_notification_types_disabled');
        $emailNotificationTypesDisabled = json_decode($emailNotificationTypesDisabled, true);
        $emailNotificationTypesSync = $this->prepareNotificationTypesSync($emailNotificationTypesDisabled);

        auth()->user()->emailNotificationsTypesDisabled()->sync($emailNotificationTypesSync);
    }

    private function syncAutomaticGeneralNotificationTypes(Request $request)
    {
        $automaticGeneralNotificationTypesDisabled = $request->input('automatic_general_notification_types_disabled');
        $automaticGeneralNotificationTypesDisabled = json_decode($automaticGeneralNotificationTypesDisabled, true);
        $automaticGeneralNotificationTypesSync = $this->prepareAutomaticNotificationTypesSync($automaticGeneralNotificationTypesDisabled);

        auth()->user()->automaticGeneralNotificationsTypesDisabled()->sync($automaticGeneralNotificationTypesSync);
    }

    private function syncAutomaticEmailNotificationTypes(Request $request)
    {
        $automaticEmailNotificationTypesDisabled = $request->input('automatic_email_notification_types_disabled');
        $automaticEmailNotificationTypesDisabled = json_decode($automaticEmailNotificationTypesDisabled, true);
        $automaticEmailNotificationTypesSync = $this->prepareAutomaticNotificationTypesSync($automaticEmailNotificationTypesDisabled);

        auth()->user()->automaticEmailNotificationsTypesDisabled()->sync($automaticEmailNotificationTypesSync);
    }

    private function prepareNotificationTypesSync($notificationTypes)
    {
        $notificationTypesSync = [];

        foreach ($notificationTypes as $notificationType) {
            $notificationTypesSync[] = [
                'uid' => generateUuid(),
                'notification_type_uid' => $notificationType,
            ];
        }

        return $notificationTypesSync;
    }

    private function prepareAutomaticNotificationTypesSync($notificationTypes)
    {
        $notificationTypesSync = [];

        foreach ($notificationTypes as $notificationType) {
            $notificationTypesSync[] = [
                'uid' => generateUuid(),
                'automatic_notification_type_uid' => $notificationType,
            ];
        }

        return $notificationTypesSync;
    }
}
