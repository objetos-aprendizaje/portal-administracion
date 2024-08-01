<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Console\Command;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Illuminate\Support\Facades\DB;

class ChangeStatusToEnrolling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-enrolling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los cursos a matriculación';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // Extraemos los cursos que están en estado INSCRIPTION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $courses = CoursesModel::where('enrolling_start_date', '<=', now())
            ->where('enrolling_finish_date', '>=', now())
            ->with(['status', 'students'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'INSCRIPTION');
            })
            ->get();

        if ($courses->count()) {
            DB::transaction(function () use ($courses) {
                $enrollingStatus = CourseStatusesModel::where('code', 'ENROLLING')->first();

                foreach ($courses as $course) {
                    $course->status()->associate($enrollingStatus);
                    $course->save();

                    $this->sendEmailAutomaticNotification($course);
                    $this->sendGeneralAutomaticNotification($course);
                }
            });
        }
    }

    private function sendEmailAutomaticNotification($course)
    {
        $enrollingFinishDateFormatted = formatDatetimeUser($course->enrolling_finish_date);

        $parameters = [
            'courseName' => $course->title,
            'enrollingFinishDate' => $enrollingFinishDateFormatted
        ];

        $studentsUsers = $this->filterUsersNotification($course->students, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'El curso ' . $course->title . ' ya está en matriculación', $parameters, 'emails.course_started_enrolling'));
        }
    }

    private function sendGeneralAutomaticNotification($course) {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')->first();

        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Curso en matriculación";
        $generalNotificationAutomatic->description = "El curso <b>" . $course->title . "</b> en el que estás inscrito, ya está en período de matriculación";
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
