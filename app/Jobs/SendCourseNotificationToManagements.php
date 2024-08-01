<?php

namespace App\Jobs;

use App\Models\AutomaticNotificationTypesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Models\UsersModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCourseNotificationToManagements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $course;

    /**
     * Create a new job instance.
     */
    public function __construct($course)
    {
        $this->course = $course;
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

        $this->sendGeneralNotification($managements, $this->course['uid']);
        $this->sendEmailNotification($managements, $this->course['title']);
    }

    private function sendGeneralNotification($managements, $courseUid)
    {
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = generate_uuid();
        $generalNotificationAutomatic->title = 'Nuevo curso para revisar';
        $generalNotificationAutomatic->description = 'Hay un nuevo curso pendiente de revisión';
        $generalNotificationAutomatic->entity_uid = $courseUid;

        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS')->first();

        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();

        $generalNotificationAutomatic->save();

        $managersFiltered = $this->filterManagers($managements, 'general');

        foreach ($managersFiltered as $management) {
            GeneralNotificationsAutomaticUsersModel::create([
                'uid' => generate_uuid(),
                'general_notifications_automatic_uid' => $generalNotificationAutomatic->uid,
                'user_uid' => $management->uid,
            ]);
        }
    }

    private function sendEmailNotification($managements, $title)
    {
        $parameters = [
            "course_title" => $title
        ];

        $filteredManagers = $this->filterManagers($managements, 'email');

        foreach ($filteredManagers as $manager) {
            dispatch(new SendEmailJob($manager['email'], 'Nuevo curso para revisar', $parameters, 'emails.course_pending_approval_managements'));
        }
    }

    private function filterManagers($managers, $notificationType)
    {
        if ($notificationType === "email") {
            return $managers->filter(function ($manager) {
                return !$manager->automaticEmailNotificationsTypesDisabled->contains('code', 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS');
            });
        } else {
            return $managers->filter(function ($manager) {
                return !$manager->automaticGeneralNotificationsTypesDisabled->contains('code', 'NEW_COURSES_NOTIFICATIONS_MANAGEMENTS');
            });
        }
    }
}
