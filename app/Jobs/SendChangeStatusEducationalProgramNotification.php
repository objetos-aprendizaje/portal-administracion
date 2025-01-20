<?php

namespace App\Jobs;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

/**
 * Envía notificación de cambio de estado de programa formativo
 */
class SendChangeStatusEducationalProgramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $educationalProgram;

    public function __construct($educationalProgram)
    {
        $this->educationalProgram = $educationalProgram;
    }

    public function handle()
    {
        $this->saveGeneralNotificationChangeStatusEducationalProgram($this->educationalProgram);
        $this->sendEmailChangeStatusEducationalProgram($this->educationalProgram);
    }

    private function sendEmailChangeStatusEducationalProgram($educationalProgram)
    {
        $parameters = [
            "educational_program_name" => $educationalProgram['name'],
            "educational_program_status" => $educationalProgram['status']['name'],
            "reason" => $educationalProgram['status_reason']
        ];

        dispatch(new SendEmailJob($educationalProgram['creator_user']['email'], 'Cambio de estado de programa formativo', $parameters, 'emails.educational_program_change_status'));
    }

    private function saveGeneralNotificationChangeStatusEducationalProgram($educationalProgram)
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomaticUid = generateUuid();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Cambio de estado de programa formativo";
        $generalNotificationAutomatic->description = "<p>El estado del programa formativo "
            . $educationalProgram['name'] . " ha cambiado a "
            . $educationalProgram['status']['name'] . ".</p>";

        if ($educationalProgram['status_reason']) {
            $generalNotificationAutomatic->description .= "<p>Motivo: " . $educationalProgram['status_reason'] . "</p>";
        }

        $generalNotificationAutomatic->entity_uid = $educationalProgram['uid'];
        $generalNotificationAutomatic->entity = "educational_program_change_status";

        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'CHANGE_STATUS_EDUCATIONAL_PROGRAM')->first();
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;

        $userToSync[] = [
            "uid" => generateUuid(),
            "user_uid" => $educationalProgram['creator_user_uid'],
            "general_notifications_automatic_uid" => $generalNotificationAutomaticUid,
            "is_read" => 0
        ];

        $generalNotificationAutomatic->save();
        $generalNotificationAutomatic->users()->sync($userToSync);
    }

}
