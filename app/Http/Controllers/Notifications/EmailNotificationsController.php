<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EmailNotificationsModel;
use App\Models\DestinationsEmailNotificationsRolesModel;
use App\Models\DestinationsEmailNotificationsUsersModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class EmailNotificationsController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $notifications = EmailNotificationsModel::get()->toArray();

        return view(
            'notifications.email.index',
            [
                "page_name" => "Notificaciones por email",
                "page_title" => "Notificaciones por email",
                "resources" => [
                    "resources/js/notifications_module/email_notifications.js"
                ],
                "notifications" => $notifications,
                "tabulator" => true,
                "tomselect" => true
            ]
        );
    }

    public function getEmailNotifications(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = EmailNotificationsModel::query();

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('subject', 'LIKE', "%{$search}%")
                    ->orWhere('body', 'LIKE', "%{$search}%");
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

    public function getEmailNotification($notification_general_uid)
    {

        if (!$notification_general_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $general_notification = EmailNotificationsModel::where('uid', $notification_general_uid)->with('roles')->with('users')->first();

        if (!$general_notification) {
            return response()->json(['message' => 'La notificación general no existe'], 406);
        }

        return response()->json($general_notification, 200);
    }

    public function saveEmailNotification(Request $request)
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

        $validator_rules = [
            'subject' => [
                'required', 'max:255',
            ],
            'notification_general_uid' => 'nullable|exists:general_notifications,uid',
            'type' => 'required',
            'body' => 'required',
            'send_date' => 'required|date|after_or_equal:now',
        ];

        $validator = Validator::make($request->all(), $validator_rules, $messages);

        // Si el tipo es de roles, se añade valicación para comprobar si se ha seleccionado algún rol
        $validator->sometimes('roles', 'required|array|min:1', function ($input) {
            return $input->type === 'ROLES';
        });

        // Si el tipo es de usuarios, se añade valicación para comprobar si se ha seleccionado algún usuario
        $validator->sometimes('users', 'required|array|min:1', function ($input) {
            return $input->type === 'USERS';
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, &$isNew) {
            $notification_email_uid = $request->get('notification_email_uid');
            $subject = $request->get('subject');
            $body = $request->get('body');
            $type = $request->get('type');
            $send_date = $request->get('send_date');

            if ($notification_email_uid) {
                $notification_email = EmailNotificationsModel::find($notification_email_uid);
                if ($notification_email->sent) {
                    throw new \Exception('La notificación ya ha sido enviada y no puede ser modificada.');
                }
                $isNew = false;
            } else {
                $notification_email_uid = generate_uuid();
                $notification_email = new EmailNotificationsModel();
                $notification_email->uid = $notification_email_uid;
                $isNew = true;
            }

            $notification_email->subject = $subject;
            $notification_email->body = $body;
            $notification_email->type = $type;
            $notification_email->send_date = $send_date;
            $notification_email->save();

            /**
             * Si la notificación está dirigida a grupos de usuarios basados en roles,
             * se registran las relaciones correspondientes en la tabla de roles y se eliminan
             * las posibles relaciones previas en la tabla de usuarios. En caso contrario,
             * se eliminan las relaciones en la tabla de roles y se registran las relaciones
             * correspondientes en la tabla de usuarios.
             */
            if ($type === 'ROLES') {
                $roles_input = $request->get('roles');
                foreach ($roles_input as $rol) {
                    DestinationsEmailNotificationsRolesModel::create([
                        "uid" => generate_uuid(),
                        "rol_uid" => $rol,
                        "email_notification_uid" => $notification_email_uid
                    ]);
                }
            } elseif ($type === 'USERS') {
                $users_input = $request->get('users');

                foreach ($users_input as $user) {
                    DestinationsEmailNotificationsUsersModel::create([
                        "uid" => generate_uuid(),
                        "user_uid" => $user,
                        "email_notification_uid" => $notification_email_uid
                    ]);
                }
            }
        }, 5);

        return response()->json([
            'message' =>  $isNew ? 'Notificación por email creada correctamente' : 'Notificación por email actualizada correctamente'
        ], 200);
    }

    public function deleteEmailNotifications(Request $request)
    {
        $uids = $request->input('uids');

        // Filtrar las notificaciones que ya han sido enviadas
        $notifications = EmailNotificationsModel::whereIn('uid', $uids)->get();
        $uids = $notifications->where('sent', false)->pluck('uid');

        EmailNotificationsModel::destroy($uids);

        return response()->json(['message' => 'Notificaciones eliminadas correctamente'], 200);
    }
}
