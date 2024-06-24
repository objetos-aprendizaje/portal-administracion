<?php

namespace App\Console\Commands;

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
    protected $description = 'Cambia el estado de los programas formativos a "Inscripción" cuando entra en período de inscripción.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extraemos los programas educativos que están en estado ACCEPTED_PUBLICATION y que tiene
        // fecha de inicio de inscripción inferior a la actual
        $educationalPrograms = EducationalProgramsModel::where('inscription_start_date', '<=', now())
            ->where('inscription_finish_date', '>=', now())
            ->with(['status', 'categories', 'tags'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'ACCEPTED_PUBLICATION');
            })
            ->get();

        if ($educationalPrograms->count()) {
            $developmentStatus = EducationalProgramStatusesModel::where('code', 'INSCRIPTION')->first();
            $educationalProgramsUids = $educationalPrograms->pluck('uid');
            $studentsUsers = $this->getAllStudents();

            DB::transaction(function () use ($educationalProgramsUids, $developmentStatus, $studentsUsers, $educationalPrograms) {
                // Cambiamos el estado de los programas educativos a DEVELOPMENT
                EducationalProgramsModel::whereIn('uid', $educationalProgramsUids)->update(['educational_program_status_uid' => $developmentStatus->uid]);

                // Notificaciones a los usuarios que están interesados en categorías de los programas educativos
                $this->sendEmailsNotificationsUsersInterested($studentsUsers, $educationalPrograms);
                $this->sendGeneralNotificationsUsersInterested($studentsUsers, $educationalPrograms);

                // Enviamos los programas educativos a la api de búsqueda para posteriormente poder buscarlos desde el front
                if (env('ENABLED_API_SEARCH')) {
                    $this->sendEducationalProgramsToApiSearch($educationalPrograms);
                }
            });
        }
    }

    private function sendGeneralNotificationsUsersInterested($studentsUsers, $educationalPrograms)
    {

        $generalNotificationsAutomaticData = [];
        $generalNotificationsAutomaticUsersData = [];


        foreach($educationalPrograms as $educationalProgram) {
            $generalNotificationAutomaticUid = generate_uuid();
            $generalNotificationsAutomaticData[] = [
                'uid' => $generalNotificationAutomaticUid,
                'title' => "Disponible nuevo programa educativo",
                'description' => "El programa educativo " . $educationalProgram->name . " que podría interesarte, está disponible para inscripción",
                'entity' => "educational_program_status_change_inscription",
                'entity_uid' => $educationalProgram->uid,
                'created_at' => now(),
            ];

            $studentsUsersFiltered = $studentsUsers->filter(function ($student) use ($educationalProgram) {
                return $student->categories->pluck('uid')->contains(function ($value) use ($educationalProgram) {
                    return $educationalProgram->categories->pluck('uid')->contains($value);
                });
            });

            foreach($studentsUsersFiltered as $student) {
                $generalNotificationsAutomaticUsersData[] = [
                    'uid' => generate_uuid(),
                    'general_notifications_automatic_uid' => $generalNotificationAutomaticUid,
                    'user_uid' => $student->uid,
                ];
            }
        }

        $generalNotificationsAutomaticDataChunks = array_chunk($generalNotificationsAutomaticData, 500);
        foreach ($generalNotificationsAutomaticDataChunks as $chunk) {
            GeneralNotificationsAutomaticModel::insert($chunk);
        }

        $generalNotificationsAutomaticUsersDataChunks = array_chunk($generalNotificationsAutomaticUsersData, 500);
        foreach ($generalNotificationsAutomaticUsersDataChunks as $chunk) {
            GeneralNotificationsAutomaticUsersModel::insert($chunk);
        }

    }

    private function sendEmailsNotificationsUsersInterested($studentsUsers, $educationalPrograms)
    {
        // Recorremos los usuarios y le buscamos los programas educativos en los que está interesado
        foreach ($studentsUsers as $user) {
            $uidsCategories = $user->categories->pluck('uid')->toArray();

            // Buscamos en el array de programas educativos los que tengan alguna categoría en común con el usuario
            $educationalProgramsFiltered = $this->filterUsersInterested($educationalPrograms, $uidsCategories);

            // Si hay programas educativos que coinciden con las categorías del usuario, se envía la notificación
            if ($educationalProgramsFiltered->count()) {
                $parametersTemplate = array_map(function ($educationalProgram) {
                    return [
                        'title' => $educationalProgram['name'],
                        'description' => $educationalProgram['description'],
                    ];
                }, $educationalProgramsFiltered->toArray());

                $emailNotificationAutomatic = new EmailNotificationsAutomaticModel();
                $emailNotificationAutomatic->uid = generate_uuid();
                $emailNotificationAutomatic->subject = 'Nuevos programas formativos disponibles';
                $emailNotificationAutomatic->user_uid = $user->uid;
                $emailNotificationAutomatic->parameters = json_encode(['educational_programs' => $parametersTemplate]);
                $emailNotificationAutomatic->template = 'recommended_educational_programs_user';
                $emailNotificationAutomatic->save();
            }
        }
    }

    private function filterUsersInterested($educationalPrograms, $uidsUserCategories)
    {
        $educationalProgramsFiltered = $educationalPrograms->filter(function ($educationalProgram) use ($uidsUserCategories) {
            return $educationalProgram->categories->pluck('uid')->contains(function ($value) use ($uidsUserCategories) {
                return in_array($value, $uidsUserCategories);
            });
        });

        return $educationalProgramsFiltered;
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
}
