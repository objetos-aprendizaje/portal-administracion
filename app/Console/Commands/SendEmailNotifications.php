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

        $emailNotifications = $this->getEmailNotifications();

        if (!$emailNotifications->count()) {
            return;
        }

        if ($this->checkParametersEmailServerIncorrect()) {
            Log::error('Error en los parámetros de configuración del servidor de correo electrónico');
            return;
        }

        $allUsers = UsersModel::with('roles')->with("emailNotificationsTypesDisabled")->get();

        foreach ($emailNotifications as $notification) {
            $this->processNotification($notification, $allUsers);
        }
    }

    private function processNotification($notification, $allUsers)
    {
        if ($notification->type == 'ALL_USERS') {
            $this->processAllUsersNotification($notification, $allUsers);
        } elseif ($notification->type == 'ROLES') {
            $this->processRolesNotification($notification, $allUsers);
        } elseif ($notification->type == 'USERS') {
            $this->processUsersNotification($notification);
        }
    }

    private function processAllUsersNotification($notification, $allUsers)
    {
        // Excluímos los usuarios que tienen deshabilitado el tipo de notificación
        $usersInterested = $this->filterUsersNotInterestedNotificationType($allUsers, $notification);

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

    private function processRolesNotification($notification, $allUsers)
    {
        // Excluímos los usuarios que tienen deshabilitado el tipo de notificación
        $usersInterested = $this->filterUsersNotInterestedNotificationType($allUsers, $notification);

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
        $users = $this->filterUsersNotInterestedNotificationType($notification->users, $notification);

        foreach ($users as $user) {
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

    private function getEmailNotifications()
    {
        return EmailNotificationsModel::where('status', 'PENDING')->with(["emailNotificationType", "users"])->get();
    }

    private function checkParametersEmailServerIncorrect()
    {
        $parametersEmailServer = Cache::get('parameters_email_service');

        return collect($parametersEmailServer)->every(function ($value) {
            return is_null($value);
        });
    }

    /**
     * Filtra los usuarios que no están interesados en el tipo de notificación
     */
    private function filterUsersNotInterestedNotificationType($users, $notification)
    {

        $usersInterested = $users->filter(function ($user) {
            return $user->email_notifications_allowed;
        });

        if($notification->notification_type_uid) {
            $usersInterested = $usersInterested->filter(function ($user) use ($notification) {
                return $notification->notification_type_uid || !$user->emailNotificationsTypesDisabled->contains('uid', $notification->notification_type_uid);
            });
        }

        return $usersInterested;
    }
}
