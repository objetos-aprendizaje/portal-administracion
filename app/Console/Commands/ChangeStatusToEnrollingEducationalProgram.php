<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use Illuminate\Console\Command;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Illuminate\Support\Facades\DB;

class ChangeStatusToEnrollingEducationalProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-enrolling-educational-program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los programas formativos a matriculación';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // Extraemos los programas formativos que están en estado INSCRIPTION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $educationalPrograms = EducationalProgramsModel::where('enrolling_start_date', '<=', now())
            ->where('enrolling_finish_date', '>=', now())
            ->with(['status', 'students'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'INSCRIPTION');
            })
            ->get();

        if ($educationalPrograms->count()) {
            DB::transaction(function () use ($educationalPrograms) {
                $enrollingStatus = EducationalProgramStatusesModel::where('code', 'ENROLLING')->first();

                foreach ($educationalPrograms as $educationalProgram) {
                    $educationalProgram->status()->associate($enrollingStatus);
                    $educationalProgram->save();
                    $this->sendGeneralAutomaticNotification($educationalProgram);
                    $this->sendEmailsNotifications($educationalProgram);
                }
            });
        }
    }

    // Prepara todos los emails de los usuarios inscritos en los programas educativos
    private function sendEmailsNotifications($educationalProgram)
    {
        $studentsUsers = $this->filterUsersNotification($educationalProgram->students, "email");
        $enrollingFinishDateFormatted = formatDatetimeUser($educationalProgram->enrolling_finish_date);

        $parameters = [
            'educationalProgramName' => $educationalProgram->name,
            'enrollingFinishDate' => $enrollingFinishDateFormatted
        ];

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'El programa formativo ' . $educationalProgram->name . ' ya está en matriculación', $parameters, 'emails.educational_program_started_enrolling'));
        }
    }

    private function sendGeneralAutomaticNotification($educationalProgram)
    {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS')->first();

        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Programa formativo en matriculación";
        $generalNotificationAutomatic->description = "El programa educativo <b>" . $educationalProgram->name . "</b> en el que estás inscrito, ya está en período de matriculación";
        $generalNotificationAutomatic->entity_uid = $educationalProgram->uid;
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        $studentsFiltered = $this->filterUsersNotification($educationalProgram->students, "general");

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
