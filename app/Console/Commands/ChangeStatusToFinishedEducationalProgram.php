<?php

namespace App\Console\Commands;

use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
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

        //dd($educationalPrograms->toArray());
        DB::transaction(function () use ($educationalPrograms) {
            $this->changeEducationalProgramStatusToFinished($educationalPrograms);
            $this->saveEmailNotificationsStudents($educationalPrograms);
            $this->saveGeneralNotificationsUsers($educationalPrograms);
        });
    }

    private function changeEducationalProgramStatusToFinished($educationalPrograms)
    {
        $finishedStatus = EducationalProgramStatusesModel::where('code', 'FINISHED')->first();
        $educationalProgramsUids = $educationalPrograms->pluck('uid')->toArray();

        EducationalProgramsModel::whereIn('uid', $educationalProgramsUids)->update(['educational_program_status_uid' => $finishedStatus->uid]);
    }

    private function saveEmailNotificationsStudents($educationalPrograms)
    {

        $emailNotificationsAutomaticData = [];

        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'Programa formativo finalizado',
                    'template' => 'educational_program_status_change_finished',
                    'user_uid' => $student->uid,
                    'parameters' => json_encode([
                        'educationalProgramTitle' => $educationalProgram->name,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $emailNotificationsAutomaticDataChunks = array_chunk($emailNotificationsAutomaticData, 500);

        foreach ($emailNotificationsAutomaticDataChunks as $chunk) {
            EmailNotificationsAutomaticModel::insert($chunk);
        }
    }

    private function saveGeneralNotificationsUsers($courses)
    {
        $generalNotificationAutomatics = [];
        $generalNotificationAutomaticUsers = [];

        foreach ($courses as $course) {
            $generalNotificationAutomaticUid = generate_uuid();

            $generalNotificationAutomatics[] = [
                'uid' => $generalNotificationAutomaticUid,
                'title' => 'Programa formativo finalizado',
                'description' => 'El programa formativo ' . $course->name . ' ha finalizado',
                'entity' => 'educational_program_status_change_finished',
                'entity_uid' => $course->uid,
                'created_at' => now()
            ];

            foreach ($course->students as $student) {
                $generalNotificationAutomaticUsers[] = [
                    'uid' => generate_uuid(),
                    'general_notifications_automatic_uid' => $generalNotificationAutomaticUid,
                    'user_uid' => $student->uid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $generalNotificationAutomaticsChunk = array_chunk($generalNotificationAutomatics, 500);
        foreach ($generalNotificationAutomaticsChunk as $chunk) {
            GeneralNotificationsAutomaticModel::insert($chunk);
        }

        $generalNotificationAutomaticUsersChunk = array_chunk($generalNotificationAutomaticUsers, 500);
        foreach ($generalNotificationAutomaticUsersChunk as $chunk) {
            GeneralNotificationsAutomaticUsersModel::insert($chunk);
        }
    }
}
