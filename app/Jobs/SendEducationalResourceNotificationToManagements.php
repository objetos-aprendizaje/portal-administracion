<?php

namespace App\Jobs;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Models\UsersModel;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

/**
 * Envía notificación de nuevo programa formativo para revisar a gestores
 */
class SendEducationalResourceNotificationToManagements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $educationalResource;

    /**
     * Create a new job instance.
     */
    public function __construct($educationalResource)
    {
        $this->educationalResource = $educationalResource;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Usuarios gestores que tienen habilitada la notificación
        $managements = UsersModel::with(['roles', 'automaticGeneralNotificationsTypesDisabled', 'automaticEmailNotificationsTypesDisabled'])
            ->whereHas('roles', function ($query) {
                $query->where('code', 'MANAGEMENT');
            })
            ->get();

        $this->sendGeneralNotification($managements, $this->educationalResource['uid']);
        $this->sendEmailNotification($managements, $this->educationalResource['title']);
    }

    private function sendGeneralNotification($managements, $educationalResourceUid)
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = generateUuid();
        $generalNotificationAutomatic->title = 'Nuevo recurso educativo para revisar';
        $generalNotificationAutomatic->description = 'Hay un nuevo recurso educativo pendiente de revisión';
        $generalNotificationAutomatic->entity_uid = $educationalResourceUid;

        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS')->first();

        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();

        $generalNotificationAutomatic->save();

        $managersFiltered = $this->filterManagers($managements, 'general');

        foreach ($managersFiltered as $management) {
            GeneralNotificationsAutomaticUsersModel::create([
                'uid' => generateUuid(),
                'general_notifications_automatic_uid' => $generalNotificationAutomatic->uid,
                'user_uid' => $management->uid,
            ]);
        }
    }

    private function sendEmailNotification($managements, $title)
    {
        $parameters = [
            "educational_resource_title" => $title
        ];

        $filteredManagers = $this->filterManagers($managements, 'email');

        foreach ($filteredManagers as $manager) {
            dispatch(new SendEmailJob($manager['email'], 'Nuevo recurso educativo para revisar', $parameters, 'emails.educational_resource_pending_approval_managements'));
        }
    }

    private function filterManagers($managers, $notificationType)
    {
        if ($notificationType === "email") {
            return $managers->filter(function ($manager) {
                return !$manager->automaticEmailNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS');
            });
        } else {
            return $managers->filter(function ($manager) {
                return !$manager->automaticGeneralNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_RESOURCES_NOTIFICATIONS_MANAGEMENTS');
            });
        }
    }
}
