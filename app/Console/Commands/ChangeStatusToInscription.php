<?php

namespace App\Console\Commands;

use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\UsersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeStatusToInscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-inscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los cursos a "Inscripción" cuando entra en período de inscripción.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos los cursos que están en estado ACCEPTED_PUBLICATION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $courses = CoursesModel::where('inscription_start_date', '<=', now())
            ->where('inscription_finish_date', '>=', now())
            ->with(['status', 'categories', 'tags'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'ACCEPTED_PUBLICATION');
            })
            ->get();

        if ($courses->count()) {
            $developmentStatus = CourseStatusesModel::where('code', 'INSCRIPTION')->first();
            $coursesUids = $courses->pluck('uid');

            DB::transaction(function () use ($coursesUids, $developmentStatus, $courses) {
                // Cambiamos el estado de los cursos a DEVELOPMENT
                CoursesModel::whereIn('uid', $coursesUids)->update(['course_status_uid' => $developmentStatus->uid]);
                $emailNotificationsAutomaticData = $this->getEmailsNotificationsUsersInterested($courses);

                $emailNotificationsAutomaticDataChunks = array_chunk($emailNotificationsAutomaticData, 500); // Divide los datos en chunks de 500 registros
                foreach ($emailNotificationsAutomaticDataChunks as $chunk) {
                    EmailNotificationsAutomaticModel::insert($chunk);
                }

                // Enviamos los cursos a la api de búsqueda para posteriormente poder buscarlos desde el front
                if(env('ENABLED_API_SEARCH')) {
                    $this->sendCoursesToApiSearch($courses);
                }
            });
        }
    }

    private function sendCoursesToApiSearch($courses) {
        $data = [];

        foreach($courses as $course) {
            $tags = $course->tags->pluck("tag")->toArray();
            $data[] = (object)[
                "uid" => $course->uid,
                "title" => $course->title,
                "description" => $course->description ?? "",
                "tags" => $tags,
            ];
        }

        $endpoint = env('API_SEARCH_URL') . '/submit_courses';
        $headers = [
            'API-KEY' => env('API_SEARCH_KEY'),
        ];

        guzzle_call($endpoint, $data, $headers, 'POST');
    }

    private function getEmailsNotificationsUsersInterested($courses)
    {

        // Todos los usuarios que son estudiantes
        $users = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })
            ->with('categories')
            ->get();

        $emailNotificationsAutomaticData = [];

        // Recorremos los usuarios y le buscamos los cursos en los que está interesado
        foreach ($users as $user) {
            $uidsCategories = $user->categories->pluck('uid')->toArray();

            // Buscamos en el array de cursos los que tengan alguna categoría en común con el usuario
            $coursesFiltered = $courses->filter(function ($course) use ($uidsCategories) {
                return $course->categories->pluck('uid')->contains(function ($value) use ($uidsCategories) {
                    return in_array($value, $uidsCategories);
                });
            });

            // Si hay cursos que coinciden con las categorías del usuario, se envía la notificación
            if ($coursesFiltered->count()) {
                $coursesTemplate = array_map(function ($course) {
                    return [
                        'title' => $course['title'],
                        'description' => $course['description'],
                    ];
                }, $coursesFiltered->toArray());

                $emailNotificationsAutomaticData[] = [
                    'uid' => generate_uuid(),
                    'subject' => 'Nuevos cursos disponibles',
                    'user_uid' => $user->uid,
                    'parameters' => json_encode(['courses' => $coursesTemplate]),
                    'template' => 'recommended_courses_user'
                ];
            }
        }

        return $emailNotificationsAutomaticData;
    }
}
