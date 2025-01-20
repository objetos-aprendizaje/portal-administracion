<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
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
            ->with(['status', 'categories', 'tags', 'blocks', 'blocks.learningResults'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'ACCEPTED_PUBLICATION');
            })
            ->get();

        if ($courses->count()) {
            $developmentStatus = CourseStatusesModel::where('code', 'INSCRIPTION')->first();
            $studentsUsers = $this->getAllStudents();

            DB::transaction(function () use ($developmentStatus, $studentsUsers, $courses) {

                foreach ($courses as $course) {
                    $course->status()->associate($developmentStatus);
                    $course->save();

                    $this->sendGeneralNotificationsUsersInterested($studentsUsers, $course);
                    $this->sendEmailsNotificationsUsersInterested($studentsUsers, $course);
                }

                // Enviamos los cursos a la api de búsqueda para posteriormente poder buscarlos desde el front
                if (env('ENABLED_API_SEARCH')) {
                    $this->sendCoursesToApiSearch($courses);
                }
            });
        }
    }

    private function sendGeneralNotificationsUsersInterested($studentsUsers, $course)
    {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'NEW_COURSES_NOTIFICATIONS')->first();

        $generalNotificationAutomaticUid = generateUuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Disponible nuevo curso";
        $generalNotificationAutomatic->description = "El curso <b>" . $course->title . "</b> que podría interesarte, está disponible para inscripción";
        $generalNotificationAutomatic->entity_uid = $course->uid;
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        // Filtramos los usuarios por los que tienen desactivadas las notificaciones de nuevos cursos
        $studentsFiltered = $this->filterUsersInterestedCourse($course, $studentsUsers, "general");

        foreach ($studentsFiltered as $student) {
            $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
            $generalNotificationAutomaticUser->uid = generateUuid();
            $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
            $generalNotificationAutomaticUser->user_uid = $student->uid;
            $generalNotificationAutomaticUser->save();
        }
    }

    private function sendEmailsNotificationsUsersInterested($studentsUsers, $course)
    {
        $parameters = [
            'course_title' => $course->title,
            'course_description' => $course->description,
        ];

        $studentsUsers = $this->filterUsersInterestedCourse($course, $studentsUsers, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'Nuevo curso disponible', $parameters, 'emails.recommended_courses_user'));
        }
    }

    private function getAllStudents()
    {
        return UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })
            ->with('categories', 'automaticGeneralNotificationsTypesDisabled', 'automaticEmailNotificationsTypesDisabled', 'learningResultsPreferences')
            ->get();
    }

    private function filterUsersInterestedCourse($course, $users, $typeNotification)
    {
        if ($typeNotification === "general") {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticGeneralNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'NEW_COURSES_NOTIFICATIONS';
                });
            });
        } else {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticEmailNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'NEW_COURSES_NOTIFICATIONS';
                });
            });
        }

        // Comprobamos si tiene categorías en común con el curso
        $usersFilteredCategories = $usersFiltered->filter(function ($user) use ($course) {
            return $user->categories->contains(function ($value) use ($course) {
                return $course->categories->contains('uid', $value->uid);
            });
        });

        $usersFilteredLearningResults = $usersFiltered->filter(function ($user) use ($course) {
            return $user->learningResultsPreferences->contains(function ($value) use ($course) {
                return $course->blocks->pluck('learningResults')->flatten()->contains('uid', $value->uid);
            });
        });

        // Mergeamos de manera única
        return $usersFilteredCategories->merge($usersFilteredLearningResults)->unique('uid');
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
