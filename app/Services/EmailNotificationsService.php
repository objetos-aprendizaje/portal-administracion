<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\UsersModel;
use Illuminate\Support\Facades\Log;

class EmailNotificationsService
{

    public function processNotification($notification)
    {
        $allUsers = UsersModel::with('roles')->with("emailNotificationsTypesDisabled")->get();

        if ($notification->type == 'ALL_USERS') {
            $this->processAllUsersNotification($notification, $allUsers);
        } elseif ($notification->type == 'ROLES') {
            $this->processRolesNotification($notification, $allUsers);
        } elseif ($notification->type == 'USERS') {
            $this->processUsersNotification($notification);
        }
    }

    public function processAllUsersNotification($notification, $allUsers)
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

                    dispatch(new SendEmailJob($user['email'], $notification['subject'], $parameters, 'emails.notification', $notification->uid));
                } catch (\Exception $e) {
                    Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
                }
            }
        }
    }

    public function processRolesNotification($notification, $allUsers)
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
                        dispatch(new SendEmailJob($user['email'], $notification['subject'], $parameters, 'emails.notification', $notification->uid));
                    } catch (\Exception $e) {
                        Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
                    }
                }
            }
        }
    }

    public function processUsersNotification($notification)
    {
        $users = $this->filterUsersNotInterestedNotificationType($notification->users, $notification);

        foreach ($users as $user) {
            try {
                $parameters = [
                    "body" => $notification['body'],
                ];

                dispatch(new SendEmailJob($user['email'], $notification['subject'], $parameters, 'emails.notification', $notification->uid));
            } catch (\Exception $e) {
                Log::error('Error enviando email a ' . $user['email'] . ' ' . $e->getMessage());
            }

            $notification->sent = true;
        }
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
                return $notification->notification_type_uid && !$user->emailNotificationsTypesDisabled->contains('uid', $notification->notification_type_uid);
            });
        }

        return $usersInterested;
    }
}
