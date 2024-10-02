<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\GeneralNotificationsModel;
use App\Models\DestinationsGeneralNotificationsRolesModel;
use App\Models\DestinationsGeneralNotificationsUsersModel;
use App\Models\GeneralNotificationTypesModel;
use App\Models\NotificationsTypesModel;
use App\Models\UserGeneralNotificationsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralNotificationsController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $notification_types = NotificationsTypesModel::get();

        return view(
            'notifications.general.index',
            [
                "page_name" => "Notificaciones generales",
                "page_title" => "Notificaciones generales",
                "resources" => [
                    "resources/js/notifications_module/general_notifications.js"
                ],
                "tabulator" => true,
                "tomselect" => true,
                "flatpickr" => true,
                "notification_types" => $notification_types,
                "submenuselected" => "notifications-general",
            ]
        );
    }

    public function getGeneralNotifications(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = GeneralNotificationsModel::query()
            ->with('roles')
            ->with('users')
            ->with('generalNotificationType')
            ->join('notifications_types', 'general_notifications.notification_type_uid', '=', 'notifications_types.uid', 'left')
            ->select('general_notifications.*', 'notifications_types.name as notification_type_name');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if ($filter['database_field'] == "notification_types") {
                    $query->whereIn('general_notifications.notification_type_uid', $filter['value']);
                } else if ($filter['database_field'] == "start_date") {
                    $query->where($filter['database_field'], '>=', $filter['value']);
                } else if ($filter['database_field'] == "end_date") {
                    $query->where($filter['database_field'], '<=', $filter['value']);
                } else if ($filter['database_field'] == "roles") {
                    $query->whereHas('roles', function ($query) use ($filter) {
                        $query->whereIn('user_roles.uid', $filter['value']);
                    });
                } else if ($filter['database_field'] == "users") {
                    $query->whereHas('users', function ($query) use ($filter) {
                        $query->whereIn('users.uid', $filter['value']);
                    });
                }
                else if($filter['database_field'] == "type"){
                    $query->where('type', $filter['value']);
                }
            }
        }



        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getGeneralNotification($notification_general_uid)
    {

        if (!$notification_general_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $general_notification = GeneralNotificationsModel::where('uid', $notification_general_uid)->with('roles')->with('users')->with('generalNotificationType')->first();

        if (!$general_notification) {
            return response()->json(['message' => 'La notificación general no existe'], 406);
        }

        return response()->json($general_notification, 200);
    }

    public function saveGeneralNotification(Request $request)
    {

        $errorsMessages = $this->validateGeneralNotification($request);

        if (!empty($errorsMessages)) {
            return response()->json(['errors' => $errorsMessages], 422);
        }

        $isNew = true;
        $notification_general_uid = $request->get('notification_general_uid');

        if ($notification_general_uid) {
            $notification_general = GeneralNotificationsModel::find($notification_general_uid);
            $isNew = false;
        } else {
            $notification_general_uid = generate_uuid();
            $notification_general = new GeneralNotificationsModel();
            $notification_general->uid = $notification_general_uid;
            $isNew = true;
        }

        $notification_general->fill($request->only([
            'title', 'description', 'start_date', 'end_date', 'type', 'notification_type_uid'
        ]));

        DB::transaction(function () use ($request, $notification_general, $isNew) {
            $notification_general->save();

            /**
             * Si la notificación está dirigida a grupos de usuarios basados en roles,
             * se registran las relaciones correspondientes en la tabla de roles y se eliminan
             * las posibles relaciones previas en la tabla de usuarios. En caso contrario,
             * se eliminan las relaciones en la tabla de roles y se registran las relaciones
             * correspondientes en la tabla de usuarios.
             */
            $type = $request->get('type');
            if ($type === 'ROLES') {
                $this->handleRoles($request, $notification_general, $isNew);
            } elseif ($type === 'USERS') {
                $this->handleUsers($request, $notification_general, $isNew);
            }

            $this->createLog($isNew);
        }, 5);

        return response()->json([
            'message' => ($isNew) ? 'Notificación general añadida correctamente' : 'Notificación general actualizada correctamente',
        ], 200);
    }

    /**
     * Devuelve un listado de usuarios que han visto una notificación general
     */
    public function getUserViewsGeneralNotification(Request $request, $general_notification_uid)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = UserGeneralNotificationsModel::query()
            ->join('users as user', 'user_general_notifications.user_uid', '=', 'user.uid')
            ->select('user.*', 'user_general_notifications.view_date as view_date');

        $query->where('general_notification_uid', $general_notification_uid);

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('user.first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('user.last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('user.email', 'ILIKE', "%{$search}%");
            });
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
     * Devuelve una notificación general para un usuario y además la marca como vista
     */
    public function getGeneralNotificationUser($notification_general_uid)
    {

        if (!$notification_general_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $user_uid = Auth::user()['uid'];

        $general_notification = GeneralNotificationsModel::where('uid', $notification_general_uid)->addSelect([
            'is_read' => UserGeneralNotificationsModel::select(DB::raw('CAST(CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS INTEGER)'))
                ->whereColumn('user_general_notifications.general_notification_uid', 'general_notifications.uid')
                ->where('user_general_notifications.user_uid', $user_uid)
                ->limit(1)
        ])
            ->first()->toArray();

        if (!$general_notification) {
            return response()->json(['message' => 'La notificación general no existe'], 406);
        }

        // La marcamos como vista
        if (!$general_notification['is_read']) {
            $user_general_notification = new UserGeneralNotificationsModel();
            $user_general_notification->uid = generate_uuid();
            $user_general_notification->user_uid = $user_uid;
            $user_general_notification->general_notification_uid = $general_notification['uid'];
            $user_general_notification->view_date = date('Y-m-d H:i:s');

            $user_general_notification->save();
        }

        return response()->json($general_notification, 200);
    }

    public function getGeneralAutomaticNotificationUser($uid)
    {
        $user_uid = Auth::user()['uid'];

        $generalNotificationAutomatic = GeneralNotificationsAutomaticModel::with(['automaticNotificationType', 'users'])->where('uid', $uid)
            ->whereHas('users', function ($query) use ($user_uid) {
                $query->where('users.uid', $user_uid);
            })->first();

        $generalNotificationAutomatic->users()->updateExistingPivot($user_uid, ['is_read' => true]);

        return response()->json($generalNotificationAutomatic);
    }

    private function validateGeneralNotification($request)
    {
        $messages = [
            'title.required' => 'El campo titulo es obligatorio.',
            'title.min' => 'El titulo no puede tener menos de 3 caracteres.',
            'title.max' => 'El titulo no puede tener más de 255 caracteres.',
            'title.unique' => 'El titulo de la notificación ya está en uso.',
            'description.required' => 'El campo descripción es obligatorio.',
            'description.min' => 'La descripción no puede tener menos de 3 caracteres.',
            'description.max' => 'La descripción no puede tener más de 255 caracteres.',
            'start_date.required' => 'El campo fecha de inicio es obligatorio.',
            'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a la fecha actual.',
            'end_date.required' => 'El campo fecha de fin es obligatorio.',
            'notification_general_uid.exists' => 'El tipo de curso no exite.',
            'roles.min' => 'Debes seleccionar al menos un rol',
            'roles.required' => 'Debes seleccionar al menos un rol',
            'notification_type_uid.required' => 'Debes seleccionar un tipo de notificación',
            'type.required' => 'Debes indicar a quién va dirigida la notificación',
        ];

        $validator_rules = [
            'title' => [
                'required', 'min:3', 'max:255',
            ],
            'description' => 'required|min:3|max:255',
            'notification_general_uid' => 'nullable|exists:general_notifications,uid',
            'end_date' => 'required',
            'type' => 'required',
        ];

        $notification_general_uid = $request->get('notification_general_uid');

        // Si estamos creando la notificación, validamos que la fecha de inicio sea posterior a la fecha actual
        if (!$notification_general_uid) {
            $validator_rules['start_date'] = 'required|date|after_or_equal:today';
        }

        $validator = Validator::make($request->all(), $validator_rules, $messages);

        // Si el tipo es de roles, se añade valicación para comprobar si se ha seleccionado algún rol
        $validator->sometimes('roles', 'required|array|min:1', function ($input) {
            return $input->type === 'ROLES';
        });

        // Si el tipo es de usuarios, se añade valicación para comprobar si se ha seleccionado algún usuario
        $validator->sometimes('users', 'required|array|min:1', function ($input) {
            return $input->type === 'USERS';
        });

        $errorsMessages = $validator->errors()->messages();

        return $errorsMessages;
    }

    function handleRoles($request, $notification_general, $isNew)
    {
        $roles_input = $request->get('roles');
        if ($isNew) {
            foreach ($roles_input as $rol) {
                DestinationsGeneralNotificationsRolesModel::create([
                    "uid" => generate_uuid(),
                    "rol_uid" => $rol,
                    "general_notification_uid" => $notification_general->uid
                ]);
            }
        } else {
            $roles = [];
            foreach ($roles_input as $rol) {
                $roles[] = [
                    'uid' => generate_uuid(),
                    'rol_uid' => $rol
                ];
            }
            $notification_general->roles()->sync($roles);
            $notification_general->users()->detach();
        }
    }

    function handleUsers($request, $notification_general, $isNew)
    {
        $users_input = $request->get('users');

        if ($isNew) {
            foreach ($users_input as $user) {
                DestinationsGeneralNotificationsUsersModel::create([
                    "uid" => generate_uuid(),
                    "user_uid" => $user,
                    "general_notification_uid" => $notification_general->uid
                ]);
            }
        } else {
            $users = [];

            foreach ($request->get('users') as $user) {
                $users[] = [
                    'uid' => generate_uuid(),
                    'user_uid' => $user
                ];
            }

            $notification_general->users()->sync($users);
            $notification_general->roles()->detach();
        }
    }

    function createLog($isNew)
    {
        $messageLog = $isNew ? 'Notificación general añadida' : 'Notificación general actualizada';
        LogsController::createLog($messageLog, 'Notificaciones generales', auth()->user()->uid);
    }

    public function deleteGeneralNotifications(Request $request)
    {

        $uids = $request->input('uids');

        DB::transaction(function () use ($uids) {
            GeneralNotificationsModel::destroy($uids);
            LogsController::createLog('Eliminar notificaciones generales', 'Notificaciones generales', auth()->user()->uid);
        });

        $general_notifications = GeneralNotificationsModel::get()->toArray();

        return response()->json(['message' => 'Notificaciones eliminadas correctamente', 'general_notifications' => $general_notifications], 200);
    }


    public function getGeneralNotificationTypes()
    {

        $general_notification_types = NotificationsTypesModel::get();

        return response()->json($general_notification_types, 200);
    }
}
