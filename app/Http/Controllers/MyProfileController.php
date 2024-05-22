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
use App\Rules\NifNie;
use Illuminate\Support\Facades\Validator;

class MyProfileController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $user = Auth::user();

        $user->load('notificationsTypesPreferences');

        $notification_types = NotificationsTypesModel::all();

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

        $notification_types = $request->input('notification_types');
        $notification_types = json_decode($notification_types, true);

        DB::transaction(function () use ($notification_types, $user, $request) {
            $notification_types_sync = [];
            foreach ($notification_types as $notification_type) {
                $notification_types_sync[] = [
                    'uid' => generate_uuid(),
                    'notification_type_uid' => $notification_type,
                    'user_uid' => $user->uid,
                ];
            }

            $user->notificationsTypesPreferences()->sync($notification_types_sync);

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
}
