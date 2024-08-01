<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeStatusToFinishedEducationalProgram extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-finished-educational-program';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los programas formativos a finalizado';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $educationalPrograms = EducationalProgramsModel::where('realization_finish_date', '<=', now())
            ->with(['status', 'students' => function ($query) {
                $query->where('status', 'ENROLLED')->where('acceptance_status', 'ACCEPTED');
            }])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['DEVELOPMENT']);
            })
            ->get();

        if (!$educationalPrograms->count()) return;

        DB::transaction(function () use ($educationalPrograms) {
            $finishedStatus = EducationalProgramStatusesModel::where('code', 'FINISHED')->first();

            foreach($educationalPrograms as $educationalProgram) {
                $educationalProgram->status()->associate($finishedStatus);
                $educationalProgram->save();

                $this->saveEmailNotificationsStudents($educationalProgram);
                $this->saveGeneralNotificationsUsers($educationalProgram);
            }
        });
    }

    private function saveEmailNotificationsStudents($educationalProgram)
    {
        $parameters = [
            'educationalProgramTitle' => $educationalProgram->name,
        ];

        $studentsUsers = $this->filterUsersNotification($educationalProgram->students, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'Programa formativo finalizado', $parameters, 'emails.educational_program_status_change_finished'));
        }
    }

    private function saveGeneralNotificationsUsers($educationalProgram)
    {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'EDUCATIONAL_PROGRAMS_ENROLLMENT_COMMUNICATIONS')->first();

        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Programa formativo finalizado";
        $generalNotificationAutomatic->description = 'El programa formativo ' . $educationalProgram->name . ' ha finalizado';
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
