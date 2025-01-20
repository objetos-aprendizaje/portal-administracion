<?php

namespace App\Jobs;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Models\UsersModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envía notificación de nuevo programa formativo para revisar a gestores
 */
class SendEducationalProgramNotificationToManagements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $educationalProgram;

    /**
     * Create a new job instance.
     */
    public function __construct($educationalProgram)
    {
        $this->educationalProgram = $educationalProgram;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Sacamos los usuarios gestores que tienen habilitada la notificación
        $managements = UsersModel::with(['roles', 'automaticGeneralNotificationsTypesDisabled', 'automaticEmailNotificationsTypesDisabled'])
            ->whereHas('roles', function ($query) {
                $query->where('code', 'MANAGEMENT');
            })
            ->get();

        $this->sendGeneralNotification($managements, $this->educationalProgram['uid']);
        $this->sendEmailNotification($managements, $this->educationalProgram['name']);
    }

    private function sendGeneralNotification($managements, $courseUid)
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = generateUuid();
        $generalNotificationAutomatic->title = 'Nuevo programa formativo para revisar';
        $generalNotificationAutomatic->description = 'Hay un nuevo programa formativo pendiente de revisión';
        $generalNotificationAutomatic->entity_uid = $courseUid;

        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS')->first();

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
            "educational_program_title" => $title
        ];

        $filteredManagers = $this->filterManagers($managements, 'email');

        foreach ($filteredManagers as $manager) {
            dispatch(new SendEmailJob($manager['email'], 'Nuevo programa formativo para revisar', $parameters, 'emails.educational_program_pending_approval_managements'));
        }
    }

    private function filterManagers($managers, $notificationType)
    {
        if ($notificationType === "email") {
            return $managers->filter(function ($manager) {
                return !$manager->automaticEmailNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS');
            });
        } else {
            return $managers->filter(function ($manager) {
                return !$manager->automaticGeneralNotificationsTypesDisabled->contains('code', 'NEW_EDUCATIONAL_PROGRAMS_NOTIFICATIONS_MANAGEMENTS');
            });
        }
    }
}
