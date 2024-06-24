<?php

namespace App\Console\Commands;

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

        if (!$educationalPrograms->count()) return;

        DB::transaction(function () use ($educationalPrograms) {
            $educationalProgramsWithValidCourses = $this->filterEducationalProgramsWithValidCourses($educationalPrograms);
            if ($educationalProgramsWithValidCourses->count()) {
                $this->changeStatusEducationalProgramsToDevelopment($educationalProgramsWithValidCourses);
                $this->sendEnrollingsToKafka($educationalPrograms);
            }

            $educationalProgramsNotReachMinStudents = $this->filterEducationalProgramsNotReachMinStudents($educationalPrograms);
            if ($educationalProgramsNotReachMinStudents->count()) {
                $this->changeStatusEducationalProgramsToPendingDecision($educationalProgramsNotReachMinStudents);
            }
        });
    }

    /**
     * @param $educationalPrograms
     * filtramos los programas educativos que tengan cursos que tengan lms_url, lms_system_uid, course_lms_uid y
     * que tengan el mínimo de estudiantes requeridos
     */
    private function filterEducationalProgramsWithValidCourses($educationalPrograms)
    {
        return $educationalPrograms->filter(function ($educationalProgram) {
            return $educationalProgram->min_required_students <= $educationalProgram->students->count() &&
                $educationalProgram->courses->filter(function ($course) {
                    return $course->lms_url && $course->lms_system_uid && $course->course_lms_uid;
                })->count() === $educationalProgram->courses->count();
        });
    }

    private function filterEducationalProgramsNotReachMinStudents($educationalPrograms)
    {
        return $educationalPrograms->filter(function ($educationalProgram) {
            return $educationalProgram->min_required_students > $educationalProgram->students->count();
        });
    }

    private function sendEnrollingsToKafka($educationalPrograms)
    {
        $kafkaService = new KafkaService();
        $coursesToSend = [];

        foreach ($educationalPrograms as $educationalProgram) {
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
        }

        $kafkaService->sendMessages($coursesToSend);
    }

    private function changeStatusEducationalProgramsToDevelopment($educationalPrograms)
    {
        $statusDevelopment = EducationalProgramStatusesModel::where('code', 'DEVELOPMENT')->first();
        $educationalProgramsUids = $educationalPrograms->pluck('uid');

        EducationalProgramsModel::whereIn('uid', $educationalProgramsUids)->update(['educational_program_status_uid' => $statusDevelopment->uid]);

        $this->saveGeneralNotificationsUsers($educationalPrograms);
        $this->saveEmailsNotificationsUsersEnrolled($educationalPrograms);
    }

    private function changeStatusEducationalProgramsToPendingDecision($educationalPrograms)
    {
        $statusPendingDecision = EducationalProgramStatusesModel::where('code', 'PENDING_DECISION')->first();

        foreach ($educationalPrograms as $educationalProgram) {
            $educationalProgram->educational_program_status_uid = $statusPendingDecision->uid;
        }

        $educationalProgram->save();
    }

    // Prepara todos los emails de los usuarios inscritos en los programas formativos
    private function saveEmailsNotificationsUsersEnrolled($educationalPrograms)
    {
        $emailNotificationsAutomaticData = [];
        foreach ($educationalPrograms as $educationalProgram) {
            foreach ($educationalProgram->students as $student) {
                $realizationFinishDateFormatted = formatDatetimeUser($educationalProgram->realization_finish_date);
                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'El programa formativo ' . $educationalProgram->name . ' ya está en período de realización',
                    'user_uid' => $student->uid,
                    'parameters' => json_encode(['educationalProgramName' => $educationalProgram->name, 'realizationFinishDate' => $realizationFinishDateFormatted]),
                    'template' => 'educational_program_started_development',
                    'created_at' => now(),
                ];
            }
        }

        $emailNotificationsAutomaticDataChunks = array_chunk($emailNotificationsAutomaticData, 500); // Divide los datos en chunks de 500 registros
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
                'description' => "El programa formativo " . $educationalProgram->name . " ya está en período de realización hasta el " . formatDatetimeUser($educationalProgram->realization_finish_date),
                'entity' => 'educational_program_started_development',
                'entity_uid' => $educationalProgram->uid,
                'created_at' => now(),
            ];

            foreach ($educationalProgram->students as $user) {
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
