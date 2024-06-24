<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use Illuminate\Support\Facades\DB;

class ChangeStatusToEnrolling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-enrolling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los cursos a matriculación';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // Extraemos los cursos que están en estado INSCRIPTION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $courses = CoursesModel::where('enrolling_start_date', '<=', now())
            ->where('enrolling_finish_date', '>=', now())
            ->with(['status', 'students'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'INSCRIPTION');
            })
            ->get();

        if ($courses->count()) {
            $enrollingStatus = CourseStatusesModel::where('code', 'ENROLLING')->first();
            $coursesUids = $courses->pluck('uid');

            DB::transaction(function () use ($coursesUids, $enrollingStatus, $courses) {
                // Cambiamos el estado de los cursos a ENROLLING
                CoursesModel::whereIn('uid', $coursesUids)->update(['course_status_uid' => $enrollingStatus->uid]);
                $emailNotificationsAutomaticData = $this->getEmailsNotificationsUsersInscribed($courses);

                // Enviamos email a los usuarios inscritos en los cursos
                $emailNotificationsAutomaticDataChunks = array_chunk($emailNotificationsAutomaticData, 500); // Divide los datos en chunks de 500 registros
                foreach ($emailNotificationsAutomaticDataChunks as $chunk) {
                    EmailNotificationsAutomaticModel::insert($chunk);
                }
            });
        }

    }

    // Prepara todos los emails de los usuarios inscritos en los cursos
    private function getEmailsNotificationsUsersInscribed($courses) {

        $emailNotificationsAutomaticData = [];
        foreach($courses as $course) {
            foreach($course->students as $student) {
                $enrollingFinishDateFormatted = formatDatetimeUser($course->enrolling_finish_date);
                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'El curso ' . $course->title . ' ya está en matriculación',
                    'user_uid' => $student->uid,
                    'parameters' => json_encode(['courseName' => $course->title, 'enrollingFinishDate' => $enrollingFinishDateFormatted]),
                    'template' => 'course_started_enrolling'
                ];
            }
        }

        return $emailNotificationsAutomaticData;
    }
}
