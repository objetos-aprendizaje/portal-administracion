<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Console\Command;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Services\KafkaService;
use Illuminate\Support\Facades\DB;

class ChangeStatusToDevelopment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-development';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los cursos a realización';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos los cursos que están en estado INSCRIPTION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $courses = CoursesModel::where('realization_start_date', '<=', now())
            ->where('realization_finish_date', '>=', now())
            ->where('belongs_to_educational_program', 0)
            ->with(['status', 'lmsSystem', 'students' => function ($query) {
                $query->where('status', 'ENROLLED')->where('acceptance_status', 'ACCEPTED');
            }])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['ENROLLING', 'INSCRIPTION']);
            })
            ->get();

        if (!$courses->count()) return;

        DB::transaction(function () use ($courses) {
            $statusesCourse = CourseStatusesModel::whereIn('code', ['DEVELOPMENT', 'PENDING_DECISION'])->get()->keyBy('code');

            foreach ($courses as $course) {
                // Si no se llega al mínimo de estudiantes, se pasa a pendiente de decisión y no se envía notificación
                if ($course->students->count() < $course->min_required_students) {
                    $course->status()->associate($statusesCourse['PENDING_DECISION']);
                    $course->save();
                } else {
                    $course->status()->associate($statusesCourse['DEVELOPMENT']);
                    $course->save();

                    $this->sendEmailsNotificationsUsersEnrolled($course);
                    $this->saveGeneralNotificationsUsers($course);

                    $this->sendEnrollingsToKafka($course);
                }
            }
        });
    }

    private function sendEnrollingsToKafka($course)
    {
        $kafkaService = new KafkaService();

        $courseToSend[] = [
            'topic' => $course->lmsSystem->identifier,
            'key' => 'course_enrollings',
            'value' => [
                'course_lms_uid' => $course->course_lms_uid,
                'course_poa_uid' => $course->uid,
                'students' => $course->students->pluck('email')->toArray()
            ]
        ];

        $kafkaService->sendMessages($courseToSend);
    }

    // Prepara todos los emails de los usuarios inscritos en los cursos
    private function sendEmailsNotificationsUsersEnrolled($course)
    {
        $realizationFinishDateFormatted = formatDatetimeUser($course->realization_finish_date);

        $parameters = [
            'course_title' => $course->title,
            'realization_finish_date' => $realizationFinishDateFormatted
        ];

        $studentsUsers = $this->filterUsersNotification($course->students, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'El curso ' . $course->title . ' ya está en período de realización', $parameters, 'emails.course_started_development'));
        }
    }

    private function saveGeneralNotificationsUsers($course)
    {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'COURSE_ENROLLMENT_COMMUNICATIONS')->first();

        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "El curso " . $course->title . " ya está en período de realización";
        $generalNotificationAutomatic->description = "El curso " . $course->title . " ya está en período de realización hasta el " . formatDatetimeUser($course->realization_finish_date);
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
