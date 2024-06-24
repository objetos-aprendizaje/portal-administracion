<?php

namespace App\Console\Commands;

use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
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

        if (!$courses->count()) return;

        DB::transaction(function () use ($courses) {
            $this->changeCourseStatusToFinished($courses);
            $this->saveEmailNotificationsStudents($courses);
            $this->saveGeneralNotificationsUsers($courses);
        });
    }

    private function changeCourseStatusToFinished($courses)
    {
        $finishedStatus = CourseStatusesModel::where('code', 'FINISHED')->first();
        $coursesUids = $courses->pluck('uid')->toArray();

        CoursesModel::whereIn('uid', $coursesUids)->update(['course_status_uid' => $finishedStatus->uid]);
    }

    private function saveEmailNotificationsStudents($courses)
    {

        $emailNotificationsAutomaticData = [];

        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'Curso finalizado',
                    'template' => 'course_status_change_finished',
                    'user_uid' => $student->uid,
                    'parameters' => json_encode([
                        'course_title' => $course->title,
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
                'title' => 'Curso finalizado',
                'description' => 'El curso ' . $course->title . ' ha finalizado',
                'entity' => 'course_status_change_finished',
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
