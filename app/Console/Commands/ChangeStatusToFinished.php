<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeStatusToFinished extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-finished';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los cursos a finalizado';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $courses = CoursesModel::where('realization_finish_date', '<=', now())
            ->where('belongs_to_educational_program', 0)
            ->with(['status', 'students' => function ($query) {
                $query->where('status', 'ENROLLED')->where('acceptance_status', 'ACCEPTED');
            }])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['DEVELOPMENT']);
            })
            ->get();

            $enrollingStatus = CourseStatusesModel::where('code', 'FINISHED')->first();

            foreach($courses as $course) {
                $course->status()->associate($enrollingStatus);
                $course->save();
                $this->sendEmailAutomaticNotification($course);
                $this->sendGeneralAutomaticNotification($course);
            }

    }

    private function sendEmailAutomaticNotification($course)
    {
        $parameters = [
            'course_title' => $course->title,
        ];

        $studentsUsers = $this->filterUsersNotification($course->students, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'El curso ' . $course->title . ' ha finalizado', $parameters, 'emails.course_status_change_finished'));
        }
    }

    private function sendGeneralAutomaticNotification($course) {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')->first();

        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Curso finalizado";
        $generalNotificationAutomatic->description = "El curso <b>" . $course->title . "</b> en el que estás inscrito, ha finalizado";
        $generalNotificationAutomatic->entity_uid = $course->uid;
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        $studentsFiltered = $this->filterUsersNotification($course->students, "general");

        foreach ($studentsFiltered as $student) {
            $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
            $generalNotificationAutomaticUser->uid = generate_uuid();
            $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
            $generalNotificationAutomaticUser->user_uid = $student->uid;
            $generalNotificationAutomaticUser->save();
        }
    }

    private function filterUsersNotification($users, $typeNotification)
    {
        $usersFiltered = [];

        if ($typeNotification == "general") {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticGeneralNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'COURSE_ENROLLMENT_COMMUNICATIONS';
                });
            });
        } else {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticEmailNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'COURSE_ENROLLMENT_COMMUNICATIONS';
                });
            });
        }

        return $usersFiltered;
    }
}
