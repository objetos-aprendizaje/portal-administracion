<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
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

        if ($courses->count()) {
            DB::transaction(function () use ($courses) {

                // Los cursos previamente deben haber sido completados con su ID de LMS y URL
                $coursesAchieveCriteriaStudents = $courses->filter(function ($course) {
                    return $course->min_required_students <= $course->students->count() && $course->lmsSystem && $course->course_lms_uid && $course->lms_url;
                });

                if ($coursesAchieveCriteriaStudents->count()) {
                    $this->changeStatusCoursesToDevelopment($coursesAchieveCriteriaStudents);
                    $this->sendEnrollingsToKafka($coursesAchieveCriteriaStudents);
                }

                // Si no se alcanza el mínimo de estudiantes, se cambia el estado a PENDING_DECISION.
                // Posteriormente el docente o gestor tendrá que decidir
                $coursesNotReachMinStudents = $courses->filter(function ($course) {
                    return $course->min_required_students > $course->students->count();
                });

                if ($coursesNotReachMinStudents->count()) {
                    $this->changeStatusCoursesToPendingDecision($coursesNotReachMinStudents);
                }
            });
        }
    }

    private function changeStatusCoursesToDevelopment($courses)
    {
        $statusDevelopment = CourseStatusesModel::where('code', 'DEVELOPMENT')->first();
        $coursesUids = $courses->pluck('uid');

        CoursesModel::whereIn('uid', $coursesUids)->update(['course_status_uid' => $statusDevelopment->uid]);

        $this->saveGeneralNotificationsUsers($courses);
        $this->saveEmailsNotificationsUsersEnrolled($courses);
    }

    private function sendEnrollingsToKafka($courses)
    {
        $kafkaService = new KafkaService();
        $coursesToSend = [];

        foreach ($courses as $course) {
            $coursesToSend[] = [
                'topic' => $course->lmsSystem->identifier,
                'key' => 'course_enrollings',
                'value' => [
                    'course_lms_uid' => $course->course_lms_uid,
                    'course_poa_uid' => $course->uid,
                    'students' => $course->students->pluck('email')->toArray()
                ]
            ];
        }

        $kafkaService->sendMessages($coursesToSend);
    }

    private function changeStatusCoursesToPendingDecision($courses)
    {
        $coursesNotReachMinStudents = $courses->filter(function ($course) {
            return $course->min_required_students > $course->students->count();
        });

        if ($coursesNotReachMinStudents->count()) {
            $statusPendingDecision = CourseStatusesModel::where('code', 'PENDING_DECISION')->first();

            foreach ($coursesNotReachMinStudents as $course) {
                $course->course_status_uid = $statusPendingDecision->uid;
            }

            $course->save();
        }
    }

    // Prepara todos los emails de los usuarios inscritos en los cursos
    private function saveEmailsNotificationsUsersEnrolled($courses)
    {
        $emailNotificationsAutomaticData = [];
        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                $realizationFinishDateFormatted = formatDatetimeUser($course->realization_finish_date);
                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'El curso ' . $course->title . ' ya está en período de realización',
                    'user_uid' => $student->uid,
                    'parameters' => json_encode(['courseName' => $course->title, 'realizationFinishDate' => $realizationFinishDateFormatted]),
                    'template' => 'course_started_enrolling'
                ];
            }
        }

        $emailNotificationsAutomaticDataChunks = array_chunk($emailNotificationsAutomaticData, 500); // Divide los datos en chunks de 500 registros
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
                'title' => "El curso " . $course->title . " ya está en período de realización",
                'description' => "El curso " . $course->title . " ya está en período de realización hasta el " . formatDatetimeUser($course->realization_finish_date),
                'entity' => 'course_status_change_realization',
                'entity_uid' => $course->uid,
                'created_at' => now(),
            ];

            foreach ($course->students as $user) {
                $generalNotificationAutomaticUsers[] = [
                    'uid' => generate_uuid(),
                    'general_notifications_automatic_uid' => $generalNotificationAutomaticUid,
                    'user_uid' => $user->uid,
                    'created_at' => now(),
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
