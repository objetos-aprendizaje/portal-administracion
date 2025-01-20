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
        $notificationsTypes = NotificationsTypesModel::get()->toArray();

        return view(
            'notifications.notifications_types.index',
            [
                "page_name" => "Tipos de notificaciones",
                "page_title" => "Tipos de notificaciones",
                "resources" => [
                    "resources/js/notifications_module/notifications_types.js"
                ],
                "notifications_types" => $notificationsTypes,
                "tabulator" => true,
                "submenuselected" => "notifications-types",
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
            $query->where('name', 'ILIKE', "%{$search}%");
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
    public function getNotificationType($notificationTypeUid)
    {

        if (!$notificationTypeUid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $notificationType = NotificationsTypesModel::where('uid', $notificationTypeUid)->first();

        if (!$notificationType) {
            return response()->json(['message' => 'El tipo de curso no existe'], 406);
        }

        return response()->json($notificationType, 200);
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
                'required',
                'min:3',
                'max:255',
                Rule::unique('notifications_types', 'name')->ignore($request->get('notification_type_uid'), 'uid'),
            ],
            'notification_type_uid' => 'nullable|exists:notifications_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Hay un error en el formulario', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $notificationTypeUid = $request->get('notification_type_uid');
        $name = $request->get('name');
        $description = $request->get('description');

        if ($notificationTypeUid) {
            $notificationType = NotificationsTypesModel::find($notificationTypeUid);
            $isNew = false;
        } else {
            $notificationType = new NotificationsTypesModel();
            $notificationType->uid = generateUuid();
            $isNew = true;
        }

        $notificationType->name = $name;
        $notificationType->description = $description;

        $notificationType->save();

        // Obtenemos todas los tipos
        $notificationsTypes = NotificationsTypesModel::get()->toArray();

        $messageLog = $isNew ? 'Creación tipo de notificación: ' : 'Tipo de notificación actualizada: ';
        $messageLog .= $notificationType->name;
        LogsController::createLog($messageLog, 'Tipos de notificaciones', auth()->user()->uid);

        return response()->json([
            'message' => ($isNew) ? 'Tipo de notificación añadida correctamente' : 'Tipo de notificación actualizada correctamente',
            'notifications_types' => $notificationsTypes
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

        $notificationTypes = NotificationsTypesModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($notificationTypes) {
            foreach ($notificationTypes as $notificationType) {
                $notificationType->delete();
                LogsController::createLog("Tipo de notificación eliminado: " . $notificationType->name, 'Tipos de notificaciones', auth()->user()->uid);
            }
        });


        $notificationsTypes = NotificationsTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de notificación eliminados correctamente', 'notifications_types' => $notificationsTypes], 200);
    }
}
