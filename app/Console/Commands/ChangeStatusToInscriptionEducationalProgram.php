<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\AutomaticNotificationTypesModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramStatusesModel;
use App\Models\EmailNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticModel;
use App\Models\GeneralNotificationsAutomaticUsersModel;
use App\Models\UsersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeStatusToInscriptionEducationalProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-status-to-inscription-educational-program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cambia el estado de los programas formativos a "Inscripción" cuando entra en período de inscripción y envía notificación general y por email a los usuarios interesados.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos los programas educativos que están en estado ACCEPTED_PUBLICATION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $educationalPrograms = EducationalProgramsModel::where('inscription_start_date', '<=', now())
            ->where('inscription_finish_date', '>=', now())
            ->with(['status', 'categories', 'tags', 'courses'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'ACCEPTED_PUBLICATION');
            })
            ->get();

        if ($educationalPrograms->count()) {
            $developmentStatus = EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();
            $studentsUsers = $this->getAllStudents();

            DB::transaction(function () use ($developmentStatus, $studentsUsers, $educationalPrograms) {
                foreach ($educationalPrograms as $educationalProgram) {
                    $educationalProgram->status()->associate($developmentStatus);
                    $educationalProgram->save();

                    $this->sendGeneralNotificationsUsersInterested($studentsUsers, $educationalProgram);
                    $this->sendEmailsNotificationsUsersInterested($studentsUsers, $educationalProgram);
                }

                // Enviamos los programas educativos a la api de búsqueda para posteriormente poder buscarlos desde el front
                if (env('ENABLED_API_SEARCH')) {
                    $this->sendEducationalProgramsToApiSearch($educationalPrograms);
                }
            });
        }
    }

    private function sendGeneralNotificationsUsersInterested($studentsUsers, $educationalProgram)
    {
        $automaticNotificationType = AutomaticNotificationTypesModel::where('code', 'NEW_EDUCATIONAL_PROGRAMS')->first();

        $generalNotificationAutomaticUid = generate_uuid();
        $generalNotificationAutomatic = new GeneralNotificationsAutomaticModel();
        $generalNotificationAutomatic->uid = $generalNotificationAutomaticUid;
        $generalNotificationAutomatic->title = "Disponible nuevo programa formativo";
        $generalNotificationAutomatic->description = "El programa formativo <b>" . $educationalProgram->name . "</b> que podría interesarte, está disponible para inscripción";
        $generalNotificationAutomatic->entity_uid = $educationalProgram->uid;
        $generalNotificationAutomatic->automatic_notification_type_uid = $automaticNotificationType->uid;
        $generalNotificationAutomatic->created_at = now();
        $generalNotificationAutomatic->save();

        // Filtramos los usuarios por los que tienen desactivadas las notificaciones de nuevos cursos
        $studentsFiltered = $this->filterUsersInterestedEducationalProgram($educationalProgram, $studentsUsers, "general");

        foreach ($studentsFiltered as $student) {
            $generalNotificationAutomaticUser = new GeneralNotificationsAutomaticUsersModel();
            $generalNotificationAutomaticUser->uid = generate_uuid();
            $generalNotificationAutomaticUser->general_notifications_automatic_uid = $generalNotificationAutomaticUid;
            $generalNotificationAutomaticUser->user_uid = $student->uid;
            $generalNotificationAutomaticUser->save();
        }
    }

    private function sendEmailsNotificationsUsersInterested($studentsUsers, $educationalProgram)
    {
        $parameters = [
            'educational_program_title' => $educationalProgram->name,
            'educational_program_description' => $educationalProgram->description,
        ];

        $studentsUsers = $this->filterUsersInterestedEducationalProgram($educationalProgram, $studentsUsers, "email");

        foreach ($studentsUsers as $user) {
            dispatch(new SendEmailJob($user->email, 'Nuevo programa formativo disponible', $parameters, 'emails.recommended_educational_programs_user'));
        }
    }

    private function getAllStudents()
    {
        return UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })
            ->with('categories')
            ->get();
    }

    private function sendEducationalProgramsToApiSearch($educationalPrograms)
    {
        $data = [];

        foreach ($educationalPrograms as $educationalProgram) {
            $tags = $educationalProgram->tags->pluck("tag")->toArray();
            $data[] = (object)[
                "uid" => $educationalProgram->uid,
                "title" => $educationalProgram->name,
                "description" => $educationalProgram->description ?? "",
                "tags" => $tags,
            ];
        }

        $endpoint = env('API_SEARCH_URL') . '/submit_learning_objects';
        $headers = [
            'API-KEY' => env('API_SEARCH_KEY'),
        ];

        guzzle_call($endpoint, $data, $headers, 'POST');
    }

    private function filterUsersInterestedEducationalProgram($educationalProgram, $users, $typeNotification)
    {
        if ($typeNotification === "general") {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticGeneralNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'NEW_EDUCATIONAL_PROGRAMS';
                });
            });
        } else {
            $usersFiltered = $users->filter(function ($user) {
                return !$user->automaticEmailNotificationsTypesDisabled->contains(function ($value) {
                    return $value->code === 'NEW_EDUCATIONAL_PROGRAMS';
                });
            });

        }

        // Comprobamos si tiene categorías en común con el curso
        $usersFilteredCategories = $usersFiltered->filter(function ($user) use ($educationalProgram) {
            return $user->categories->contains(function ($value) use ($educationalProgram) {
                return $educationalProgram->categories->contains('uid', $value->uid);
            });
        });

        // Resultados de aprendizaje
        $usersFilteredLearningResults = $usersFiltered->filter(function ($user) use ($educationalProgram) {
            return $user->learningResultsPreferences->contains(function ($value) use ($educationalProgram) {
                return $educationalProgram->courses->contains(function ($course) use ($value) {
                    return $course->blocks->pluck('learningResults')->flatten()->contains('uid', $value->uid);
                });
            });
        });

        // Mergeamos de manera única
        return $usersFilteredCategories->merge($usersFilteredLearningResults)->unique('uid');
    }
}
