<?php

namespace App\Jobs;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\UsersModel;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Envía notificación de cambio de estado de curso
 */
class SendChangeStatusCourseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    public function handle()
    {
        $userCreator = $this->course->creatorUser->with([
            'automaticEmailNotificationsTypesDisabled',
            'automaticGeneralNotificationsTypesDisabled'
        ])->first();

        if (!$userCreator->automaticEmailNotificationsTypesDisabled->contains('code', 'CHANGE_STATUS_COURSE')) {
            $this->sendEmailChangeStatusCourse($this->course);
        }

        if (!$userCreator->automaticGeneralNotificationsTypesDisabled->contains('code', 'CHANGE_STATUS_COURSE')) {
            $this->saveNotificationChangeStatusCourse($this->course);
        }
    }

    private function saveNotificationChangeStatusCourse($course)
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomaticUid = generateUuid();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Cambio de estado de curso";
        $generalNotificationAutomatic->description = "<p>El estado del curso "
            . $course->title . " ha cambiado a "
            . $course->status->name . ".</p>";

        if ($course['status_reason']) {
            $generalNotificationAutomatic->description .= "<p>Motivo: " . $course->status_reason . "</p>";
        }

        $generalNotificationAutomatic->entity_uid = $course['uid'];
        $generalNotificationAutomatic->entity = "course_change_status";

        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'CHANGE_STATUS_COURSE')->first();
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;

        $userToSync[] = [
            "uid" => generateUuid(),
            "user_uid" => $course->creator_user_uid,
            "general_notifications_automatic_uid" => $generalNotificationAutomaticUid,
            "is_read" => 0
        ];

        $generalNotificationAutomatic->save();
        $generalNotificationAutomatic->users()->sync($userToSync);
    }

    private function sendEmailChangeStatusCourse($course)
    {
        $parameters = [
            "course_name" => $course->title,
            "course_status" => $course->status->name,
            "reason" => $course->status_reason
        ];

        dispatch(new SendEmailJob($course->creatorUser->email, 'Cambio de estado de curso', $parameters, 'emails.course_change_status'));
    }
}
