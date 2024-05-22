<?php

namespace App\Console\Commands;

use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use Illuminate\Console\Command;
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
    protected $description = 'Cambia el estado de los cursos a desarrollo si ha comenzado el período y envía una notificación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos los cursos que están en estado INSCRIPTION y que tiene
        // fecha de inicio de desarrollo inferior a la actual
        $courses = CoursesModel::where('realization_start_date', '<=', now())
            ->where('realization_finish_date', '>=', now())
            ->with(['status', 'students' => function ($query) {
                $query->where('approved', 1)->where('status', 'ENROLLED');
            }])
            ->whereHas('status', function ($query) {
                $query->where('code', 'INSCRIPTION');
            })
            ->get();

        if ($courses->count()) {
            $developmentStatus = CourseStatusesModel::where('code', 'DEVELOPMENT')->first();

            // Cambiamos el estado de los cursos a DEVELOPMENT
            $coursesUids = $courses->pluck('uid');

            DB::transaction(function () use ($coursesUids, $developmentStatus, $courses) {
                CoursesModel::whereIn('uid', $coursesUids)->update(['course_status_uid' => $developmentStatus->uid]);
                $this->sendNotificationsUsersCourse($courses);
                $this->sendNotificationsEmailUsersCourses($courses);
            });
        }
    }

    /**
     * Envía notificaciones a los usuarios de los cursos
     * Guarda en la tabla general_notifications_automatic y general_notifications_automatic_users
     * @param $courses
     */
    private function sendNotificationsUsersCourse($courses)
    {

        $generalNotificationsAutomaticData = [];
        $generalNotificationsAutomaticUsersData = [];

        foreach ($courses as $course) {

            $generalNotificationAutomaticUid = generate_uuid();
            $generalNotificationsAutomaticData[] = [
                'uid' => $generalNotificationAutomaticUid,
                'title' => "Ha comenzado el período de realización del curso {$course->title}",
                'description' => "El curso {$course->title} ha comenzado su período de realización. No te lo pierdas.",
                'entity' => 'course',
                'entity_uid' => $course->uid
            ];

            $students = $course->students;

            foreach ($students as $student) {
                $generalNotificationsAutomaticUsersData[] = [
                    'uid' => generate_uuid(),
                    'general_notifications_automatic_uid' => $generalNotificationAutomaticUid,
                    'user_uid' => $student->uid,
                    'is_read' => 0
                ];
            }
        }

        GeneralNotificationsAutomaticModel::insert($generalNotificationsAutomaticData);
        GeneralNotificationsAutomaticUsersModel::insert($generalNotificationsAutomaticUsersData);
    }

    private function sendNotificationsEmailUsersCourses($courses) {
        $notificationsEmailUsersCourses = [];

        foreach($courses as $course) {
            foreach($course->students as $student) {

                $parameters = [
                    'course_title' => $course->title,
                    'realization_finish_date' => $course->realization_finish_date,
                ];

                $notificationsEmailUsersCourses[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'El curso' . $course->title . 'ha comenzado',
                    'parameters' => json_encode($parameters),
                    'template' => 'course_started_development',
                    'user_uid' => $student->uid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $notificationsEmailUsersCoursesChunks = array_chunk($notificationsEmailUsersCourses, 500);
        foreach ($notificationsEmailUsersCoursesChunks as $chunk) {
            EmailNotificationsAutomaticModel::insert($chunk);
        }
    }
}
