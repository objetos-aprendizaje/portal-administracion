<?php

namespace App\Console\Commands;

use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
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

            $studentsUsers = $this->getAllStudents();

            DB::transaction(function () use ($coursesUids, $developmentStatus, $studentsUsers, $courses) {
                // Cambiamos el estado de los cursos a DEVELOPMENT
                CoursesModel::whereIn('uid', $coursesUids)->update(['course_status_uid' => $developmentStatus->uid]);

                // Notificaciones a los usuarios que están interesados en categorías de los cursos
                $this->sendEmailsNotificationsUsersInterested($studentsUsers, $courses);
                $this->sendGeneralNotificationsUsersInterested($studentsUsers, $courses);

                // Enviamos los cursos a la api de búsqueda para posteriormente poder buscarlos desde el front
                if (env('ENABLED_API_SEARCH')) {
                    $this->sendCoursesToApiSearch($courses);
                }
            });
        }
    }

    private function sendGeneralNotificationsUsersInterested($studentsUsers, $courses)
    {
        // Filtramos los usuarios por los que tienen desactivadas las notificaciones de nuevos cursos
        $studentsUsersFiltered = $studentsUsers->filter(function ($user) {
            return !$user->automaticGeneralNotificationsTypesDisabled->contains(function ($value) {
                return $value->code === 'NEW_COURSES_NOTIFICATIONS';
            });
        });

        foreach ($studentsUsersFiltered as $user) {
            $uidsCategories = $user->categories->pluck('uid')->toArray();

            // Buscamos en el array de cursos los que tengan alguna categoría en común con el usuario
            $coursesFiltered = $this->filterUsersInterested($courses, $uidsCategories);

            if ($coursesFiltered->count()) {
                foreach ($coursesFiltered as $course) {
                    $generalNotificationAutomaticUid = generate_uuid();
                    $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
                    $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
                    $generalNotificationAutomatic->title = "Disponible nuevo curso";
                    $generalNotificationAutomatic->description = "El curso " . $course->title . " que podría interesarte, está disponible para inscripción";
                    $generalNotificationAutomatic->entity = "course_status_change_inscription";
                    $generalNotificationAutomatic->entity_uid = $course->uid;
                    $generalNotificationAutomatic->created_at = now();
                    $generalNotificationAutomatic->save();

                    $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
                    $generalNotificationAutomaticUser->uid = generate_uuid();
                    $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
                    $generalNotificationAutomaticUser->user_uid = $user->uid;
                    $generalNotificationAutomaticUser->save();
                }
            }
        }
    }

    private function sendEmailsNotificationsUsersInterested($studentsUsers, $courses)
    {
        // Filtramos los usuarios por los que tienen desactivadas las notificaciones de nuevos cursos
        $studentsUsersFiltered = $studentsUsers->filter(function ($user) {
            return !$user->automaticEmailNotificationsTypesDisabled->contains(function ($value) {
                return $value->code === 'NEW_COURSES_NOTIFICATIONS';
            });
        });

        // Recorremos los usuarios y le buscamos los cursos en los que está interesado
        foreach ($studentsUsersFiltered as $user) {
            $uidsCategories = $user->categories->pluck('uid')->toArray();

            // Buscamos en el array de cursos los que tengan alguna categoría en común con el usuario
            $coursesFiltered = $this->filterUsersInterested($courses, $uidsCategories);

            // Si hay cursos que coinciden con las categorías del usuario, se envía la notificación
            if ($coursesFiltered->count()) {
                $coursesTemplate = array_map(function ($course) {
                    return [
                        'title' => $course['title'],
                        'description' => $course['description'],
                    ];
                }, $coursesFiltered->toArray());

                $emailNotificationAutomatic = new EmailNotificationsAutomaticModel();
                $emailNotificationAutomatic->uid = generate_uuid();
                $emailNotificationAutomatic->subject = 'Nuevos cursos disponibles';
                $emailNotificationAutomatic->user_uid = $user->uid;
                $emailNotificationAutomatic->parameters = json_encode(['courses' => $coursesTemplate]);
                $emailNotificationAutomatic->template = 'recommended_courses_user';
                $emailNotificationAutomatic->save();
            }
        }
    }

    private function filterUsersInterested($courses, $uidsUserCategories)
    {
        $coursesFiltered = $courses->filter(function ($course) use ($uidsUserCategories) {
            return $course->categories->pluck('uid')->contains(function ($value) use ($uidsUserCategories) {
                return in_array($value, $uidsUserCategories);
            });
        });

        return $coursesFiltered;
    }

    private function getAllStudents()
    {
        return UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })
            ->with('categories', 'automaticGeneralNotificationsTypesDisabled', 'automaticEmailNotificationsTypesDisabled')
            ->get();
    }

    private function sendCoursesToApiSearch($courses)
    {
        $data = [];

        foreach ($courses as $course) {
            $tags = $course->tags->pluck("tag")->toArray();
            $data[] = (object)[
                "uid" => $course->uid,
                "title" => $course->title,
                "description" => $course->description ?? "",
                "tags" => $tags,
            ];
        }

        $endpoint = env('API_SEARCH_URL') . '/submit_learning_objects';
        $headers = [
            'API-KEY' => env('API_SEARCH_KEY'),
        ];

        guzzle_call($endpoint, $data, $headers, 'POST');
    }
}
