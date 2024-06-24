<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
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
            $enrollingStatus = EducationalProgramStatusesModel::where('code', 'ENROLLING')->first();
            $educationalProgramsUids = $educationalPrograms->pluck('uid');

            DB::transaction(function () use ($educationalProgramsUids, $enrollingStatus, $educationalPrograms) {
                // Cambiamos el estado de los programas educativos a ENROLLING
                EducationalProgramsModel::whereIn('uid', $educationalProgramsUids)->update(['educational_program_status_uid' => $enrollingStatus->uid]);

                $this->sendEmailsNotifications($educationalPrograms);
                $this->saveGeneralNotificationsUsers($educationalPrograms);
            });
        }
    }

    // Prepara todos los emails de los usuarios inscritos en los programas educativos
    private function sendEmailsNotifications($educationalPrograms)
    {
        $emailNotificationsAutomaticData = [];
        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $enrollingFinishDateFormatted = formatDatetimeUser($educationalProgram->enrolling_finish_date);
                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'El programa formativo ' . $educationalProgram->name . ' ya está en matriculación',
                    'user_uid' => $student->uid,
                    'parameters' => json_encode(['educationalProgramName' => $educationalProgram->name, 'enrollingFinishDate' => $enrollingFinishDateFormatted]),
                    'template' => 'educational_program_started_enrolling'
                ];
            }
        }

        // Enviamos email a los usuarios inscritos en los programas educativos
        $emailNotificationsAutomaticDataChunks = array_chunk($emailNotificationsAutomaticData, 500);
        foreach ($emailNotificationsAutomaticDataChunks as $chunk) {
            EmailNotificationsAutomaticModel::insert($chunk);
        }

    }

    private function saveGeneralNotificationsUsers($educationalPrograms)
    {
        $generalNotificationAutomatics = [];
        $generalNotificationAutomaticUsers = [];

        foreach ($educationalPrograms as $educationalProgram) {
            $generalNotificationAutomaticUid = generate_uuid();
            $generalNotificationAutomatics[] = [
                'uid' => $generalNotificationAutomaticUid,
                'title' => "El programa formativo " . $educationalProgram->name . " ya está en período de realización",
                'description' => "El programa educativo " . $educationalProgram->name . " ya está en período de matriculación hasta el " . formatDatetimeUser($educationalProgram->enrolling_finish_date),
                'entity' => 'educational_program_status_change_realization',
                'entity_uid' => $educationalProgram->uid,
                'created_at' => now(),
            ];

            foreach ($educationalProgram->students as $student) {
                $generalNotificationAutomaticUsers[] = [
                    'uid' => generate_uuid(),
                    'general_notifications_automatic_uid' => $generalNotificationAutomaticUid,
                    'user_uid' => $student->uid,
                    'created_at' => now(),
                ];
            }
        }

        $generalNotificationAutomaticsChunks = array_chunk($generalNotificationAutomatics, 500);
        foreach ($generalNotificationAutomaticsChunks as $chunk) {
            GeneralNotificationsAutomaticModel::insert($chunk);
        }

        $generalNotificationAutomaticUsersChunks = array_chunk($generalNotificationAutomaticUsers, 500);
        foreach ($generalNotificationAutomaticUsersChunks as $chunk) {
            GeneralNotificationsAutomaticUsersModel::insert($chunk);
        }
    }
}
