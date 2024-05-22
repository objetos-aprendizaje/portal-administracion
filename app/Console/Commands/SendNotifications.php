<?php

namespace App\Console\Commands;

use App\Models\EmailNotificationsModel;
use App\Models\UsersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Cache;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notifications';

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

        $all_users = UsersModel::with('roles')->get();

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
        $all_users_chunks = array_chunk($all_users->toArray(), 200);
        foreach ($all_users_chunks as $usersChunk) {
            foreach ($usersChunk as $user) {
                try {
                    $parameters = [
                        "body" => $notification['body'],
                    ];

                    dispatch(new SendNotificationJob($user['email'], $notification['subject'], $parameters, 'emails.notification'));
                } catch (\Exception $e) {
                    Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
                }
            }
        }
    }

    private function processRolesNotification($notification, $all_users)
    {
        // Tipo de notificación de roles
        foreach ($notification->roles as $role) {

            // Extraemos los usuarios correspondientes al rol
            $usersWithRole = $all_users->filter(function ($user) use ($role) {
                return $user->roles->contains('uid', $role->uid);
            });

            $usersWithRoleChunk = array_chunk($usersWithRole->toArray(), 200);

            foreach ($usersWithRoleChunk as $usersChunk) {
                foreach ($usersChunk as $user) {
                    try {
                        $parameters = [
                            "body" => $notification['body'],
                        ];
                        dispatch(new SendNotificationJob($user['email'], $notification['subject'], $parameters, 'emails.notification'));
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

        foreach ($users as $user) {
            try {
                $parameters = [
                    "body" => $notification['body'],
                ];

                dispatch(new SendNotificationJob($user['email'], $notification['subject'], $parameters, 'emails.notification'));
            } catch (\Exception $e) {
                Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
            }

            $notification->sent = true;
        }
    }

    private function getEmailNotifications()
    {
        $email_notifications = EmailNotificationsModel::where('sent', 0)->get();

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
}
