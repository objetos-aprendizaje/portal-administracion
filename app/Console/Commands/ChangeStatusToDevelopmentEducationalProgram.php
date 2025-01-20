<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Console\Command;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Services\KafkaService;
use Illuminate\Support\Facades\DB;

class ChangeStatusToDevelopmentEducationalProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-development-educational-program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los programas formativos a realización';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos los programas educativos que están en estado INSCRIPTION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $educationalPrograms = EducationalProgramsModel::where('realization_start_date', '<=', now())
            ->where('realization_finish_date', '>=', now())
            ->with(['status', 'courses', 'courses.lmsSystem', 'students' => function ($query) {
                $query->where('status', 'ENROLLED')->where('acceptance_status', 'ACCEPTED');
            }])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['ENROLLING', 'INSCRIPTION']);
            })
            ->get();

        if (!$educationalPrograms->count()) {
            return;
        }

        DB::transaction(function () use ($educationalPrograms) {

            $statusesEducationalProgram = EducationalProgramStatusesModel::whereIn('code', ['DEVELOPMENT', 'PENDING_DECISION'])->get()->keyBy('code');
            foreach ($educationalPrograms as $educationalProgram) {

                // Si no se llega al mínimo de estudiantes, se pasa a pendiente de decisión y no se envía notificación
                if ($educationalProgram->students->count() < $educationalProgram->min_required_students) {
                    $educationalProgram->status()->associate($statusesEducationalProgram['PENDING_DECISION']);
                    $educationalProgram->save();
                } else {
                    $educationalProgram->status()->associate($statusesEducationalProgram['DEVELOPMENT']);
                    $educationalProgram->save();

                    $this->saveGeneralNotificationsUsers($educationalProgram);
                    $this->saveEmailsNotificationsUsersEnrolled($educationalProgram);
                    $this->sendEnrollingsToKafka($educationalProgram);
                }
            }
        });
    }

    private function sendEnrollingsToKafka($educationalProgram)
    {
        $kafkaService = new KafkaService();
        $coursesToSend = [];

        foreach ($educationalProgram->courses as $course) {
            $coursesToSend[] = [
                'topic' => $course->lmsSystem->identifier,
                'key' => 'course_enrollings',
                'value' => [
                    'course_lms_uid' => $course->course_lms_uid,
                    'course_poa_uid' => $course->uid,
                    'students' => $educationalProgram->students->pluck('email')->toArray()
                ]
            ];
        }

        $kafkaService->sendMessages($coursesToSend);
    }

    // Prepara todos los emails de los usuarios inscritos en los programas formativos
    private function saveEmailsNotificationsUsersEnrolled($educationalProgram)
    {
        $realizationFinishDateFormatted = formatDatetimeUser($educationalProgram->realization_finish_date);

        $parameters = [
            'educationalProgramName' => $educationalProgram->name,
            'realizationFinishDate' => $realizationFinishDateFormatted
        ];

        $studentsUsers = $this->filterUsersNotification($educationalProgram->students, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'El programa formativo ' . $educationalProgram->name . ' ya está en período de realización', $parameters, 'emails.educational_program_started_development'));
        }
    }

    private function saveGeneralNotificationsUsers($educationalProgram)
    {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS')->first();

        $generalNotificationAutomaticUid = generateUuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "El programa formativo " . $educationalProgram->name . " ya está en período de realización";
        $generalNotificationAutomatic->description = "El programa formativo " . $educationalProgram->name . " ya está en período de realización hasta el " . formatDatetimeUser($educationalProgram->realization_finish_date);
        $generalNotificationAutomatic->entity_uid = $educationalProgram->uid;
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        $studentsFiltered = $this->filterUsersNotification($educationalProgram->students, "general");

        foreach ($studentsFiltered as $student) {
            $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
            $generalNotificationAutomaticUser->uid = generateUuid();
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
                    return $value->code === 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS';
                });
            });
        } else {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticEmailNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS';
                });
            });
        }

        return $usersFiltered;
    }
}
