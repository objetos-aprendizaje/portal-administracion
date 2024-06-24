<?php

namespace App\Console\Commands;

use App\Models\EmailNotificationsModel;
use App\Models\UsersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Cache;

class SendEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-email-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía las notificaciones por correo electrónico.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('ENVÍO DE EMAILS: ' . now());

        $email_notifications = $this->getEmailNotifications();

        if (!$email_notifications->count()) return;

        if ($this->checkParametersEmailServerIncorrect()) {
            Log::error('Error en los parámetros de configuración del servidor de correo electrónico');
            return;
        }

        $all_users = UsersModel::with('roles')->with("emailNotificationsTypesDisabled")->get();

        foreach ($email_notifications as $notification) {
            $this->processNotification($notification, $all_users);
        }
    }

    private function processNotification($notification, $all_users)
    {
        if ($notification->type == 'ALL_USERS') {
            $this->processAllUsersNotification($notification, $all_users);
        } else if ($notification->type == 'ROLES') {
            $this->processRolesNotification($notification, $all_users);
        } else if ($notification->type == 'USERS') {
            $this->processUsersNotification($notification);
        }

        $notification->sent = true;
        $notification->save();
    }

    private function processAllUsersNotification($notification, $all_users)
    {
        // Excluímos los usuarios que tienen deshabilitado el tipo de notificación
        $usersInterested = $this->filterUsersNotInterestedNotificationType($all_users, $notification);

        $userChunks = array_chunk($usersInterested->toArray(), 200);
        foreach ($userChunks as $usersChunk) {
            foreach ($usersChunk as $user) {
                try {
                    $parameters = [
                        "body" => $notification['body'],
                    ];

                    dispatch(new SendEmailJob($user['email'], $notification['subject'], $parameters, 'emails.notification'));
                } catch (\Exception $e) {
                    Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
                }
            }
        }
    }

    private function processRolesNotification($notification, $all_users)
    {
        // Excluímos los usuarios que tienen deshabilitado el tipo de notificación
        $usersInterested = $this->filterUsersNotInterestedNotificationType($all_users, $notification);

        // Tipo de notificación de roles
        foreach ($notification->roles as $role) {

            // Extraemos los usuarios correspondientes al rol
            $usersWithRole = $usersInterested->filter(function ($user) use ($role) {
                return $user->roles->contains('uid', $role->uid);
            });

            $usersWithRoleChunk = array_chunk($usersWithRole->toArray(), 200);

            foreach ($usersWithRoleChunk as $usersChunk) {
                foreach ($usersChunk as $user) {
                    try {
                        $parameters = [
                            "body" => $notification['body'],
                        ];
                        dispatch(new SendEmailJob($user['email'], $notification['subject'], $parameters, 'emails.notification'));
                    } catch (\Exception $e) {
                        Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
                    }
                }
            }
        }
    }

    private function processUsersNotification($notification)
    {
        $users = $notification['users'];

        $users = $this->filterUsersNotInterestedNotificationType($users, $notification);

        foreach ($users as $user) {
            try {
                $parameters = [
                    "body" => $notification['body'],
                ];

                dispatch(new SendEmailJob($user['email'], $notification['subject'], $parameters, 'emails.notification'));
            } catch (\Exception $e) {
                Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
            }

            $notification->sent = true;
        }
    }

    private function getEmailNotifications()
    {
        $email_notifications = EmailNotificationsModel::where('sent', 0)->with("emailNotificationType")->get();

        return $email_notifications;
    }

    private function checkParametersEmailServerIncorrect()
    {
        $parameters_email_server = Cache::get('parameters_email_service');

        $allNull = collect($parameters_email_server)->every(function ($value) {
            return is_null($value);
        });

        return $allNull;
    }

    /**
     * Filtra los usuarios que no están interesados en el tipo de notificación
     */
    private function filterUsersNotInterestedNotificationType($users, $notification)
    {

        $usersEnabledNotificationsEmail = $users->filter(function ($user) {
            return $user->email_notifications_allowed;
        });

        $usersInterestedCategory = $usersEnabledNotificationsEmail->filter(function ($user) use ($notification) {
            return $notification->notification_type_uid || !$user->emailNotificationsTypesDisabled->contains('uid', $notification->notification_type_uid);
        });

        return $usersInterestedCategory;
    }
}
