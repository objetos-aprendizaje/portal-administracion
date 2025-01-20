<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EmailNotificationsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\NotificationsTypesModel;
use App\Services\EmailNotificationsService;
use Illuminate\Http\Request;

class EmailNotificationsController extends BaseController
{
    protected $emailNotificationsService;

    public function __construct(EmailNotificationsService $emailNotificationsService)
    {
        $this->emailNotificationsService = $emailNotificationsService;
    }

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $notificationTypes = NotificationsTypesModel::get();

        return view(
            'notifications.email.index',
            [
                "page_name" => "Notificaciones por email",
                "page_title" => "Notificaciones por email",
                "resources" => [
                    "resources/js/notifications_module/email_notifications.js"
                ],
                "tabulator" => true,
                "tomselect" => true,
                "flatpickr" => true,
                "notification_types" => $notificationTypes,
                "submenuselected" => "notifications-email",
            ]
        );
    }

    public function getEmailNotifications(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = EmailNotificationsModel::query()
            ->with('emailNotificationType')
            ->with('roles')
            ->with('users')
            ->join('notifications_types', 'email_notifications.notification_type_uid', '=', 'notifications_types.uid', 'left')
            ->select('email_notifications.*', 'notifications_types.name as notification_type_name');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('subject', 'ILIKE', "%{$search}%")
                    ->orWhere('body', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        if ($filters) {
            $this->applyFilters($filters, $query);
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getEmailNotification($notificationGeneralUid)
    {
        //Se comenta ya que  la misma ruta comprueba si tiene parametros o no.
        // if (!$notificationGeneralUid) {
        //     return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        // }

        $emailNotification = EmailNotificationsModel::where('uid', $notificationGeneralUid)->with('roles')->with('users')->with('emailNotificationType')->first();

        if (!$emailNotification) {
            return response()->json(['message' => 'La notificación general no existe'], 406);
        }

        return response()->json($emailNotification, 200);
    }

    private function validateEmailNotification($request)
    {
        $messages = [
            'subject.required' => 'El campo asunto es obligatorio.',
            'subject.max' => 'El titulo no puede tener más de 255 caracteres.',
            'body.required' => 'El cuerpo es obligatorio.',
            'body.max' => 'El cuerpo no puede tener más de 255 caracteres.',
            'roles.min' => 'Debes seleccionar al menos un rol',
            'roles.required' => 'Debes seleccionar al menos un rol',
            'type.required' => 'Debes seleccionar el tipo de destinatario',
            'send_date.required' => 'Debes seleccionar la fecha de envío',
            'send_date.after_or_equal' => 'La fecha de envío no puede ser anterior a la fecha actual',
        ];

        $validatorRules = [
            'subject' => [
                'required', 'max:255',
            ],
            'notification_general_uid' => 'nullable|exists:email_notifications,uid',
            'type' => 'required',
            'body' => 'required',
            'send_date' => 'nullable|date|after_or_equal:now',
        ];

        $validator = Validator::make($request->all(), $validatorRules, $messages);

        // Si el tipo es de roles, se añade valicación para comprobar si se ha seleccionado algún rol
        $validator->sometimes('roles', 'required|array|min:1', function ($input) {
            return $input->type === 'ROLES';
        });

        // Si el tipo es de usuarios, se añade valicación para comprobar si se ha seleccionado algún usuario
        $validator->sometimes('users', 'required|array|min:1', function ($input) {
            return $input->type === 'USERS';
        });

        return $validator->errors();
    }

    public function saveEmailNotification(Request $request)
    {

        $validatorErrors = $this->validateEmailNotification($request);

        if ($validatorErrors->any()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 400);
        }

        $notificationEmailUid = $request->get('notification_email_uid');

        if ($notificationEmailUid) {
            $notificationEmail = EmailNotificationsModel::where('uid', $notificationEmailUid)->with(['roles', 'users'])->first();
            if ($notificationEmail->status == "SENT") {
                throw new \Exception('La notificación ya ha sido enviada y no puede ser modificada.');
            }
            $isNew = false;
        } else {
            $notificationEmailUid = generateUuid();
            $notificationEmail = new EmailNotificationsModel();
            $notificationEmail->uid = $notificationEmailUid;
            $isNew = true;
        }

        $notificationEmail->fill($request->only([
            'subject', 'body', 'type', 'end_date', 'send_date', 'notification_type_uid'
        ]));

        $sendDate = $request->get('send_date');

        if($sendDate){
            $notificationEmail->send_date = $sendDate;
        } else {
            $notificationEmail->send_date = now();
        }

        if($notificationEmail->status == 'FAILED') {
            $notificationEmail->status = 'PENDING';
        }

        DB::transaction(function () use ($request, $notificationEmail, $sendDate) {

            $notificationEmail->save();

            /**
             * Si la notificación está dirigida a grupos de usuarios basados en roles,
             * se registran las relaciones correspondientes en la tabla de roles y se eliminan
             * las posibles relaciones previas en la tabla de usuarios. En caso contrario,
             * se eliminan las relaciones en la tabla de roles y se registran las relaciones
             * correspondientes en la tabla de usuarios.
             */

            $type = $request->get('type');

            if ($type === 'ROLES') {
                $roles = $request->get('roles');
                $this->handleRoles($roles, $notificationEmail);
            } elseif ($type === 'USERS') {
                $users = $request->get('users');
                $this->handleUsers($users, $notificationEmail);
            }

            // Si no hay fecha de envío, se envía la notificación inmediatamente
            if(!$sendDate) {
                $this->emailNotificationsService->processNotification($notificationEmail);
            }
        }, 5);

        return response()->json([
            'message' =>  $isNew ? 'Notificación por email creada correctamente' : 'Notificación por email actualizada correctamente'
        ], 200);
    }

    private function handleRoles($roles, $notificationEmail)
    {
        $rolesData = [];
        foreach ($roles as $role) {
            $rolesData[$role] = ['email_notification_uid' => $notificationEmail->uid, 'uid' => generateUuid()];
        }
        $notificationEmail->roles()->sync($rolesData);
        $notificationEmail->users()->detach();
    }

    private function handleUsers($users, $notificationEmail)
    {
        $usersData = [];
        foreach ($users as $user) {

            $usersData[$user] = ['email_notification_uid' => $notificationEmail->uid, 'uid' => generateUuid()];
        }
        $notificationEmail->users()->sync($usersData);
        $notificationEmail->roles()->detach();
    }

    public function deleteEmailNotifications(Request $request)
    {
        $uids = $request->input('uids');

        // Filtrar las notificaciones que ya han sido enviadas
        $notifications = EmailNotificationsModel::whereIn('uid', $uids)->get();
        $uids = $notifications->where('status', 'PENDING')->pluck('uid');

        EmailNotificationsModel::destroy($uids);

        return response()->json(['message' => 'Notificaciones eliminadas correctamente'], 200);
    }


    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == "status") {
                $query->where("status", $filter['value']);
            } elseif ($filter['database_field'] == "notification_types") {
                $query->whereIn('email_notifications.notification_type_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'send_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('send_date', '<=', $filter['value'][1])
                        ->where('send_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('send_date', '<=', $filter['value'])
                        ->whereDate('send_date', '>=', $filter['value']);
                }
            }elseif ($filter['database_field'] == "roles") {
                $query->whereHas('roles', function ($query) use ($filter) {
                    $query->whereIn('user_roles.uid', $filter['value']);
                });
            } elseif ($filter['database_field'] == "users") {
                $query->whereHas('users', function ($query) use ($filter) {
                    $query->whereIn('users.uid', $filter['value']);
                });
            }
        }
    }
    public function getEmailNotificationTypes()
    {

        $emailNotificationTypes = NotificationsTypesModel::get();

        return response()->json($emailNotificationTypes, 200);
    }
}
