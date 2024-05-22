<?php

namespace App\Http\Controllers\Notifications;

use App\Models\GeneralNotificationsModel;
use App\Models\NotificationsTypesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Logs\LogsController;
use Illuminate\Support\Facades\DB;

class NotificationsTypesController extends BaseController
{
    public function index()
    {
        $notifications_types = NotificationsTypesModel::get()->toArray();

        return view(
            'notifications.notifications_types.index',
            [
                "page_name" => "Tipos de notificaciones",
                "page_title" => "Tipos de notificaciones",
                "resources" => [
                    "resources/js/notifications_module/notifications_types.js"
                ],
                "notifications_types" => $notifications_types,
                "tabulator" => true
            ]
        );
    }


    /**
     * Obtiene todas los tipo de notificación.
     */
    public function getNotificationsTypes(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = NotificationsTypesModel::query();

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
     * Obtiene un tipo de notificación por uid
     */
    public function getNotificationType($notification_type_uid)
    {

        if (!$notification_type_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $notification_type = NotificationsTypesModel::where('uid', $notification_type_uid)->first();

        if (!$notification_type) {
            return response()->json(['message' => 'El tipo de curso no existe'], 406);
        }

        return response()->json($notification_type, 200);
    }

    /**
     * Guarda una tipo de curso. Si recibe un uid, actualiza el tipo de curso con ese uid.
     */
    public function saveNotificationType(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre no puede tener menos de 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'El nombre del tipo ya está en uso.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 'min:3', 'max:255',
                Rule::unique('notifications_types', 'name')->ignore($request->get('notification_type_uid'), 'uid'),
            ],
            'notification_type_uid' => 'nullable|exists:notifications_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Hay un error en el formulario', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $notification_type_uid = $request->get('notification_type_uid');
        $name = $request->get('name');

        if ($notification_type_uid) {
            $notification_type = NotificationsTypesModel::find($notification_type_uid);
            $isNew = false;
        } else {
            $notification_type = new NotificationsTypesModel();
            $notification_type->uid = generate_uuid();
            $isNew = true;
        }

        $notification_type->name = $name;

        $notification_type->save();

        // Obtenemos todas los tipos
        $notifications_types = NotificationsTypesModel::get()->toArray();

        $messageLog = $isNew ? 'Tipo de notificación añadida' : 'Tipo de notificación actualizada';
        LogsController::createLog($messageLog, 'Tipos de notificación', auth()->user()->uid);

        return response()->json([
            'message' => ($isNew) ? 'Tipo de notificación añadida correctamente' : 'Tipo de notificación actualizada correctamente',
            'notifications_types' => $notifications_types
        ], 200);
    }

    public function deleteNotificationsTypes(Request $request)
    {

        $uids = $request->input('uids');

        // Comprobamos si hay notificaciones que estén vinculado a los tipos de notificación
        $existNotifications = GeneralNotificationsModel::whereIn('notification_type_uid', $uids)->exists();

        if ($existNotifications) {
            return response()->json(['message' => 'No se pueden eliminar los tipos de notificación porque hay notificaciones vinculadas a ellos'], 406);
        }

        DB::transaction(function () use ($uids) {
            NotificationsTypesModel::destroy($uids);
            LogsController::createLog("Tipos de notificación eliminados", 'Tipos de notificación', auth()->user()->uid);
        });


        $notifications_types = NotificationsTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de notificación eliminados correctamente', 'notifications_types' => $notifications_types], 200);
    }
}
