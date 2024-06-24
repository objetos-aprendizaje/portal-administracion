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
use App\Rules\NifNie;
use Illuminate\Support\Facades\Validator;

class MyProfileController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $notification_types = NotificationsTypesModel::all();
        $automaticNotificationTypes = AutomaticNotificationTypesModel::all();

        $user = Auth::user();
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
                'notification_types' => $notification_types,
                "user" => $user,
                "submenuselected" => "my_profile",
                "userGeneralNotificationsDisabled" => $userGeneralNotificationsDisabled,
                "userEmailNotificationsDisabled" => $userEmailNotificationsDisabled,
                "automaticNotificationTypes" => $automaticNotificationTypes,
                "userAutomaticGeneralNotificationsDisabled" => $userAutomaticGeneralNotificationsDisabled,
                "userAutomaticEmailNotificationsDisabled" => $userAutomaticEmailNotificationsDisabled
            ]
        );
    }

    public function updateUser(Request $request)
    {
        $user = Auth::user();

        $messages = [
            'nif.required' => 'El campo NIF es obligatorio',
            'nif.max' => 'El campo NIF no puede tener más de 9 caracteres',
        ];

        $validator = Validator::make($request->all(), [
            'nif' => ['required', 'max:9', new NifNie],
            'photo_path' => 'max:6144'
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->nif = $request->input('nif');
        $user->curriculum = $request->input('curriculum');
        $user->general_notifications_allowed = $request->input('general_notifications_allowed');
        $user->email_notifications_allowed = $request->input('email_notifications_allowed');

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

    private function syncGeneralNotificationTypes(Request $request)
    {
        $general_notification_types_disabled = $request->input('general_notification_types_disabled');
        $general_notification_types_disabled = json_decode($general_notification_types_disabled, true);
        $general_notification_types_sync = $this->prepareNotificationTypesSync($general_notification_types_disabled);

        auth()->user()->generalNotificationsTypesDisabled()->sync($general_notification_types_sync);
    }

    private function syncEmailNotificationTypes(Request $request)
    {
        $email_notification_types_disabled = $request->input('email_notification_types_disabled');
        $email_notification_types_disabled = json_decode($email_notification_types_disabled, true);
        $email_notification_types_sync = $this->prepareNotificationTypesSync($email_notification_types_disabled);

        auth()->user()->emailNotificationsTypesDisabled()->sync($email_notification_types_sync);
    }

    private function syncAutomaticGeneralNotificationTypes(Request $request)
    {
        $automatic_general_notification_types_disabled = $request->input('automatic_general_notification_types_disabled');
        $automatic_general_notification_types_disabled = json_decode($automatic_general_notification_types_disabled, true);
        $automatic_general_notification_types_sync = $this->prepareAutomaticNotificationTypesSync($automatic_general_notification_types_disabled);

        auth()->user()->automaticGeneralNotificationsTypesDisabled()->sync($automatic_general_notification_types_sync);
    }

    private function syncAutomaticEmailNotificationTypes(Request $request)
    {
        $automatic_email_notification_types_disabled = $request->input('automatic_email_notification_types_disabled');
        $automatic_email_notification_types_disabled = json_decode($automatic_email_notification_types_disabled, true);
        $automatic_email_notification_types_sync = $this->prepareAutomaticNotificationTypesSync($automatic_email_notification_types_disabled);

        auth()->user()->automaticEmailNotificationsTypesDisabled()->sync($automatic_email_notification_types_sync);
    }

    private function prepareNotificationTypesSync($notification_types)
    {
        $notification_types_sync = [];

        foreach ($notification_types as $notification_type) {
            $notification_types_sync[] = [
                'uid' => generate_uuid(),
                'notification_type_uid' => $notification_type,
            ];
        }

        return $notification_types_sync;
    }

    private function prepareAutomaticNotificationTypesSync($notification_types)
    {
        $notification_types_sync = [];

        foreach ($notification_types as $notification_type) {
            $notification_types_sync[] = [
                'uid' => generate_uuid(),
                'automatic_notification_type_uid' => $notification_type,
            ];
        }

        return $notification_types_sync;
    }
}
