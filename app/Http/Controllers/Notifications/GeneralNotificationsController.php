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
        $notificationTypes = NotificationsTypesModel::get();

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
                "notification_types" => $notificationTypes,
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


        // Se agregó el nombre de la tabla para que pasara en las pruebas unitarias
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('general_notifications.title', 'ILIKE', "%{$search}%") // Especificar la tabla
                      ->orWhere('general_notifications.description', 'ILIKE', "%{$search}%"); // Especificar la tabla
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
                } elseif ($filter['database_field'] == "start_date") {
                    $query->where($filter['database_field'], '>=', $filter['value']);
                } elseif ($filter['database_field'] == "end_date") {
                    $query->where($filter['database_field'], '<=', $filter['value']);
                } elseif ($filter['database_field'] == "roles") {
                    $query->whereHas('roles', function ($query) use ($filter) {
                        $query->whereIn('user_roles.uid', $filter['value']);
                    });
                } elseif ($filter['database_field'] == "users") {
                    $query->whereHas('users', function ($query) use ($filter) {
                        $query->whereIn('users.uid', $filter['value']);
                    });
                }
                elseif($filter['database_field'] == "type"){
                    $query->where('type', $filter['value']);
                }
            }
        }

        $data = $query->paginate($size);

        adaptDatesModel($data, ['start_date', 'end_date'], true);

        return response()->json($data, 200);
    }

    public function getGeneralNotification($notificationGeneralUid)
    {
        $generalNotification = GeneralNotificationsModel::where('uid', $notificationGeneralUid)->with('roles')->with('users')->with('generalNotificationType')->first();

        if (!$generalNotification) {
            return response()->json(['message' => 'La notificación general no existe'], 406);
        }

        adaptDatesModel($generalNotification, ['start_date', 'end_date'], false);

        return response()->json($generalNotification, 200);
    }

    public function saveGeneralNotification(Request $request)
    {

        $errorsMessages = $this->validateGeneralNotification($request);

        if (!empty($errorsMessages)) {
            return response()->json(['errors' => $errorsMessages], 422);
        }

        $isNew = true;
        $notificationGeneralUid = $request->get('notification_general_uid');

        if ($notificationGeneralUid) {
            $notificationGeneral = GeneralNotificationsModel::find($notificationGeneralUid);
            $isNew = false;
        } else {
            $notificationGeneralUid = generateUuid();
            $notificationGeneral = new GeneralNotificationsModel();
            $notificationGeneral->uid = $notificationGeneralUid;
            $isNew = true;
        }

        $request->merge([
            'start_date' => adaptDateToUTC($request->get('start_date'))->format('Y-m-d H:i:s'),
            'end_date' => adaptDateToUTC($request->get('end_date'))->format('Y-m-d H:i:s')
        ]);

        $notificationGeneral->fill($request->only([
            'title', 'description', 'start_date', 'end_date', 'type', 'notification_type_uid'
        ]));

        DB::transaction(function () use ($request, $notificationGeneral, $isNew) {
            $notificationGeneral->save();

            /**
             * Si la notificación está dirigida a grupos de usuarios basados en roles,
             * se registran las relaciones correspondientes en la tabla de roles y se eliminan
             * las posibles relaciones previas en la tabla de usuarios. En caso contrario,
             * se eliminan las relaciones en la tabla de roles y se registran las relaciones
             * correspondientes en la tabla de usuarios.
             */
            $type = $request->get('type');
            if ($type === 'ROLES') {
                $this->handleRoles($request, $notificationGeneral, $isNew);
            } elseif ($type === 'USERS') {
                $this->handleUsers($request, $notificationGeneral, $isNew);
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
    public function getUserViewsGeneralNotification(Request $request, $generalNotificationUid)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = UserGeneralNotificationsModel::query()
            ->join('users as user', 'user_general_notifications.user_uid', '=', 'user.uid')
            ->select('user.*', 'user_general_notifications.view_date as view_date');

        $query->where('general_notification_uid', $generalNotificationUid);

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
    public function getGeneralNotificationUser($notificationGeneralUid)
    {

        $userUid = Auth::user()['uid'];

        $generalNotification = GeneralNotificationsModel::where('uid', $notificationGeneralUid)->addSelect([
            'is_read' => UserGeneralNotificationsModel::select(DB::raw('CAST(CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS INTEGER)'))
                ->whereColumn('user_general_notifications.general_notification_uid', 'general_notifications.uid')
                ->where('user_general_notifications.user_uid', $userUid)
                ->limit(1)
        ])
            ->first();//se quitó el toArray(), para prueba unitaria porque si es nulo no se puede verificar el 406

        if (!$generalNotification) {
            return response()->json(['message' => 'La notificación general no existe'], 406);
        }

        $generalNotification = $generalNotification->toArray();//Se agregó esta línea despues del error 406. Si no es null continua su proceso normal.

        // La marcamos como vista
        if (!$generalNotification['is_read']) {
            $userGeneralNotification = new UserGeneralNotificationsModel();
            $userGeneralNotification->uid = generateUuid();
            $userGeneralNotification->user_uid = $userUid;
            $userGeneralNotification->general_notification_uid = $generalNotification['uid'];
            $userGeneralNotification->view_date = date('Y-m-d H:i:s');

            $userGeneralNotification->save();
        }

        return response()->json($generalNotification, 200);
    }

    public function getGeneralAutomaticNotificationUser($uid)
    {
        $userUid = Auth::user()['uid'];

        $generalNotificationAutomatic = GeneralNotificationsAutomaticModel::with(['automaticNotificationType', 'users'])->where('uid', $uid)
            ->whereHas('users', function ($query) use ($userUid) {
                $query->where('users.uid', $userUid);
            })->first();

        $generalNotificationAutomatic->users()->updateExistingPivot($userUid, ['is_read' => true]);

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

        $validatorRules = [
            'title' => [
                'required', 'min:3', 'max:255',
            ],
            'description' => 'required|min:3|max:255',
            'notification_general_uid' => 'nullable|exists:general_notifications,uid',
            'end_date' => 'required',
            'type' => 'required',
        ];

        $notificationGeneralUid = $request->get('notification_general_uid');

        // Si estamos creando la notificación, validamos que la fecha de inicio sea posterior a la fecha actual
        if (!$notificationGeneralUid) {
            $validatorRules['start_date'] = 'required|date|after_or_equal:today';
        }

        $validator = Validator::make($request->all(), $validatorRules, $messages);

        // Si el tipo es de roles, se añade valicación para comprobar si se ha seleccionado algún rol
        $validator->sometimes('roles', 'required|array|min:1', function ($input) {
            return $input->type === 'ROLES';
        });

        // Si el tipo es de usuarios, se añade valicación para comprobar si se ha seleccionado algún usuario
        $validator->sometimes('users', 'required|array|min:1', function ($input) {
            return $input->type === 'USERS';
        });

        return $validator->errors()->messages();
    }

    private function handleRoles($request, $notificationGeneral, $isNew)
    {
        $rolesInput = $request->get('roles');
        if ($isNew) {
            foreach ($rolesInput as $rol) {
                DestinationsGeneralNotificationsRolesModel::create([
                    "uid" => generateUuid(),
                    "rol_uid" => $rol,
                    "general_notification_uid" => $notificationGeneral->uid
                ]);
            }
        } else {
            $roles = [];
            foreach ($rolesInput as $rol) {
                $roles[] = [
                    'uid' => generateUuid(),
                    'rol_uid' => $rol
                ];
            }
            $notificationGeneral->roles()->sync($roles);
            $notificationGeneral->users()->detach();
        }
    }

    private function handleUsers($request, $notificationGeneral, $isNew)
    {
        $usersInput = $request->get('users');

        if ($isNew) {
            foreach ($usersInput as $user) {
                DestinationsGeneralNotificationsUsersModel::create([
                    "uid" => generateUuid(),
                    "user_uid" => $user,
                    "general_notification_uid" => $notificationGeneral->uid
                ]);
            }
        } else {
            $users = [];

            foreach ($request->get('users') as $user) {
                $users[] = [
                    'uid' => generateUuid(),
                    'user_uid' => $user
                ];
            }

            $notificationGeneral->users()->sync($users);
            $notificationGeneral->roles()->detach();
        }
    }

    private function createLog($isNew)
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

        $generalNotifications = GeneralNotificationsModel::get()->toArray();

        return response()->json(['message' => 'Notificaciones eliminadas correctamente', 'general_notifications' => $generalNotifications], 200);
    }
    
}
