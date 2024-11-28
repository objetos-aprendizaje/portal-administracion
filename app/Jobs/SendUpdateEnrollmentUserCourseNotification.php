<?php

namespace App\Jobs;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;


/**
 * Envío de notificaciones generales y por email al usuario cuando se actualiza el estado de una inscripción a un curso
 */
class SendUpdateEnrollmentUserCourseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $courseStudent;

    public function __construct($courseStudent)
    {
        $this->courseStudent = $courseStudent;
        $this->courseStudent->load(['course', 'user', 'user.automaticGeneralNotificationsTypesDisabled', 'user.automaticEmailNotificationsTypesDisabled']);
    }

    public function handle()
    {
        if(!$this->courseStudent->user->automaticGeneralNotificationsTypesDisabled->contains('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')) {
            $this->sendAutomaticGeneralNotification();
        }

        if(!$this->courseStudent->user->automaticEmailNotificationsTypesDisabled->contains('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')) {
            $this->sendAutomaticEmailNotification();
        }
    }

    private function sendAutomaticGeneralNotification()
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;

        if ($this->courseStudent->acceptance_status == "ACCEPTED") {
            $generalNotificationAutomatic->title = "Inscripción a curso aceptada";
            $generalNotificationAutomatic->description = "Tu inscripción en el curso " . $this->courseStudent->course->title . " ha sido aceptada";
        } else {
            $generalNotificationAutomatic->title = "Inscripción a curso rechazada";
            $generalNotificationAutomatic->description = "Tu inscripción en el curso " . $this->courseStudent->course->title . " ha sido rechazada";
        }

        $generalNotificationAutomatic->entity = "course";
        $generalNotificationAutomatic->entity_uid = $this->courseStudent->course->uid;

        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')->first();
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
        $generalNotificationAutomaticUser->uid = generate_uuid();
        $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomaticUser->user_uid = $this->courseStudent->user_uid;
        $generalNotificationAutomaticUser->save();
    }

    private function sendAutomaticEmailNotification(){
        $emailParameters = [
            'course_title' => $this->courseStudent->course->title,
        ];

        if ($this->courseStudent->acceptance_status == "ACCEPTED") {
            $subject = "Inscripción a curso aceptada";
            $emailParameters["status"] = "ACCEPTED";
        } else {
            $subject = "Inscripción a curso rechazada";
            $emailParameters["status"] = "REJECTED";
        }

        dispatch(new SendEmailJob($this->courseStudent->user->email, $subject, $emailParameters, 'emails.course_inscription_status'));
    }
}
