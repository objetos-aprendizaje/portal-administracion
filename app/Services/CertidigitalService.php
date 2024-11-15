<?php

namespace App\Services;

use App\Exceptions\OperationFailedException;
use App\Models\CertidigitalAchievementsModel;
use App\Models\CertidigitalActivitiesModel;
use App\Models\CertidigitalAssesmentsModel;
use App\Models\CertidigitalCredentialsModel;
use App\Models\CertidigitalLearningOutcomesModel;
use App\Models\CoursesModel;
use App\Models\CoursesStudentsModel;
use Exception;
use Illuminate\Support\Facades\Http;

class CertidigitalService
{
    public function __construct() {}

    public function getValidToken()
    {
        $token = session('certidigital_token');
        $tokenExpires = session('certidigital_token_expires');

        if (!$token || !$tokenExpires || now()->greaterThan($tokenExpires)) {
            $token = $this->refreshToken();
        }

        return $token;
    }

    public function createCoursesCredentials($courses)
    {
        $achievementsCourses = [];

        foreach ($courses as $course) {
            // Recargar el curso
            $course = CoursesModel::where('uid', $course->uid)->with([
                "educational_program",
                "embeddings",
                "blocks.learningResults",
                "blocks.subBlocks.elements.subElements",
                "certidigitalCredential",
            ])->first();

            // Si ya tiene una credencial, se vacía todo lo que hay en ella
            if ($course->certidigitalCredential) {
                $this->cleanCredential($course->certidigitalCredential);
            }

            // Todos los resultados de aprendizaje GLOBALES para incluir SUBLOGROS
            $learningResults = $this->getAllLearningResultsCourse($course);
            $assesmentCourse = $this->createAssesment($course->title);
            $achievementsLearningResults = [];
            foreach ($learningResults as $learningResult) {
                $learningOutcome = $this->createLearningOutcome($learningResult['name'], null);
                $assesment = $this->createAssesment($learningResult['name'], null, $learningResult['uid']);

                // Buscamos los bloques que contienen el resultado de aprendizaje
                $achievementsBlocks = [];
                foreach ($course->blocks as $block) {
                    foreach ($block->learningResults as $learningResultBlock) {
                        if ($learningResultBlock->uid == $learningResult['uid']) {
                            $assesmentLearningResultBlock = $this->createAssesment($learningResultBlock->name, $block->uid, $learningResultBlock->uid);
                            $achievementLearningResultBlock = $this->createAchievement($learningResultBlock->name . ' B:' . $block->name, null, null, [$assesmentLearningResultBlock]);
                            $achievementsBlocks[] = $achievementLearningResultBlock;
                        }
                    }
                }

                // Creación de los logros globales
                $achievementGlobal =  $this->createAchievement($learningResult['name'], $achievementsBlocks, [$learningOutcome], [$assesment]);
                $achievementsLearningResults[] = $achievementGlobal;
            }

            // Actividades
            $activities = [];
            foreach ($course->blocks as $block) {
                $activities[] = $this->createActivity($block->name);
            }

            $achievementsCourses[] = $this->createAchievement($course->title, $achievementsLearningResults, null, [$assesmentCourse], $activities);
        }

        $credential = $this->createCredential($course->title, $course->description, $achievementsCourses);

        $course->certidigital_credential_uid = $credential['ocbid'];
        $course->save();

        return $credential;
    }

    public function emissionCredentials($courseUid)
    {
        $course = CoursesModel::where('uid', $courseUid)->with([
            'certidigitalCredential',
            'certidigitalAssesments',
            'blocks.learningResults.certidigitalAssesments' => function ($query) use ($courseUid) {
                $query->whereIn('course_block_uid', function ($subQuery) use ($courseUid) {
                    $subQuery->select('uid')
                        ->from('course_blocks')
                        ->where('course_uid', $courseUid);
                });
            },
            'students.courseBlocksLearningResultsCalifications' => function ($query) use ($courseUid) {
                $query->whereIn('course_block_uid', function ($subQuery) use ($courseUid) {
                    $subQuery->select('uid')
                        ->from('course_blocks')
                        ->where('course_uid', $courseUid);
                });
            },
            'students.courseLearningResultCalifications' => function ($query) use ($courseUid) {
                $query->where('course_uid', $courseUid);
            }
        ])->first();

        // Sacamos todos los resultados de aprendizaje de todos los bloques
        $allLearningResults = $course->blocks->map(function ($block) {
            return $block->learningResults;
        })->flatten()->unique("uid");

        $recipients = [];
        foreach ($course->students as $student) {

            $entities = [];
            $basicDataStudent = $this->getBasicDataEmissionCredential($student, $course->certidigitalCredential->uid);

            $fields = [];
            $fields = array_merge($fields, $basicDataStudent);

            // Poner nota por cada resultado de aprendizaje de cada bloque
            foreach ($course->blocks as $block) {
                foreach ($block->learningResults as $learningResult) {
                    // Calificación correspondiente al bloque y al resultado
                    $calification = $student->courseBlocksLearningResultsCalifications->filter(function ($calification) use ($learningResult, $block) {
                        return $calification->course_block_uid === $block->uid && $calification->learning_result_uid === $learningResult->uid;
                    })->first();

                    if (!$calification) continue;

                    $certidigitalAssesment = $course->certidigitalAssesments->filter(function ($assesment) use ($learningResult, $block) {
                        return $assesment->learning_result_uid === $learningResult->uid && $assesment->course_block_uid === $block->uid;
                    })->first();

                    $calificationData = [
                        [
                            'fieldPathIdentifier' => "{#{$course->certidigitalCredential->uid}}.ASM{{$certidigitalAssesment->uid}}.grade.noteLiteral(es)",
                            'value' => $calification->calification_info,
                        ]
                    ];

                    $fields = array_merge($fields, $calificationData);
                }
            }

            // Nota por cada resultado de aprendizaje global
            foreach ($allLearningResults as $learningResult) {
                $calification = $student->courseLearningResultCalifications->filter(function ($calification) use ($learningResult) {
                    return $calification->learning_result_uid === $learningResult->uid;
                })->first();

                if (!$calification) continue;

                $assesmentLearningResult = $course->certidigitalAssesments->filter(function ($assesment) use ($learningResult) {
                    return $assesment->learning_result_uid === $learningResult->uid && !$assesment->course_block_uid;
                })->first();

                $calificationData = [
                    [
                        'fieldPathIdentifier' => "{#{$course->certidigitalCredential->uid}}.ASM{{$assesmentLearningResult->uid}}.grade.noteLiteral(es)",
                        'value' => $calification->calification_info,
                    ]
                ];

                $fields = array_merge($fields, $calificationData);
            }

            // Incluir la calificación global
            $assesmentGlobal = $course->certidigitalAssesments->filter(function ($assesment) {
                return !$assesment->learning_result_uid && !$assesment->course_block_uid;
            })->first();

            $calificationData = [
                [
                    'fieldPathIdentifier' => "{#{$course->certidigitalCredential->uid}}.ASM{{$assesmentGlobal->uid}}.grade.noteLiteral(es)",
                    'value' => $student->course_student_info->calification_info,
                ]
            ];

            $fields = array_merge($fields, $calificationData);

            $entities[] = [
                "fields" => $fields
            ];

            $recipients[] = [
                "entities" => $entities
            ];
        }

        $finalStructure = [
            "recipients" => $recipients
        ];

        //Todo: Enviar a Certidigital
        $this->emitCredentials($course, $finalStructure);
    }

    private function emitCredentials($course, $data)
    {
        $generalOptions = app('general_options');

        // Emisión del bloque
        $credentialUid = $course->certidigitalCredential->id;
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials/{$credentialUid}/issuecredentials?issuingCenterId={$generalOptions['certidigital_center_id']}&locale=es";
        $response = $this->sendRequest($endpoint, $data, false);

        $emissions = $response->json();
        foreach ($emissions as $emission) {
            // Buscar el estudiante
            $student = $course->students->filter(function ($student) use ($emission) {
                return $student->email === $emission['address'];
            })->first();

            // Actualizar el estudiante con la emisión
            CoursesStudentsModel::where('user_uid', $student->uid)
                ->where('course_uid', $course->uid)
                ->update([
                    'emissions_block_id' => $emission['emissionsBlockId'],
                    'emissions_block_uuid' => $emission['uuid'],
                ]);
        }
    }

    private function getBasicDataEmissionCredential($student, $certidigitalCredentialUid)
    {
        $fields = [
            'givenName' => $student->first_name,
            'familyName' => $student->last_name,
            'primaryDeliveryAddress' => $student->email,
        ];

        $data = [];
        foreach ($fields as $field => $value) {
            $data[] = [
                'fieldPathIdentifier' => "{#{$certidigitalCredentialUid}}.REC.{$field}",
                'value' => $value,
            ];
        }

        return $data;
    }

    private function cleanCredential($credential)
    {
        $this->cleanElementsAchievement($credential->achievements);
    }

    // TODO
    private function cleanElementsAchievement($achievements)
    {
        foreach ($achievements as $achievement) {

            if (isset($achievement->activities) && count($achievement->activities)) {
                $this->deleteActivities($achievement->activities);
            }

            if ($achievement->subAchievements) {
                $this->cleanElementsAchievement($achievement->subAchievements);
            }
        }
    }

    private function deleteActivities($activities)
    {
        $generalOptions = app('general_options');

        foreach ($activities as $activity) {
            $activityOid = $activity['id'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/activities/$activityOid";

            $this->sendRequest($endpoint, [], false, "delete");

            $activity->delete();
        }
    }

    private function deleteAssesments($assesments)
    {
        $generalOptions = app('general_options');

        foreach ($assesments as $activity) {
            $activityOid = $activity['id'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/assessments/$activityOid";

            $this->sendRequest($endpoint, [], false, "delete");

            $activity->delete();
        }
    }

    private function deleteLearningOutcomes($learningOutcomes)
    {
        $generalOptions = app('general_options');

        foreach ($learningOutcomes as $learningOutcome) {
            $learningOutcomeOid = $learningOutcome['oid'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/learning-outcomes/$learningOutcomeOid";

            $this->sendRequest($endpoint, [], false, "delete");

            $learningOutcome->delete();
        }
    }

    private function deleteAchievements($achievements)
    {
        $generalOptions = app('general_options');

        foreach ($achievements as $activity) {
            $activityOid = $activity['oid'];
            $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/activities/$activityOid";

            $this->sendRequest($endpoint, [], false, "delete");

            $activity->delete();
        }
    }

    private function vinculateAssesmentToAchievement($assesment, $achievement)
    {
        $generalOptions = app('general_options');
        $achievementOid = $achievement['oid'];
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements/{$achievementOid}/provenBy?issuingCenterId={$generalOptions['certidigital_center_id']}";
        $data = [
            "oid" => [$assesment['oid']]
        ];

        $this->sendRequest($endpoint, $data, false);

        CertidigitalAssesmentsModel::where('uid', $assesment['ocbid'])->update(['certidigital_achievement_uid' => $achievement['ocbid']]);
    }

    // Se recorre todos los bloques del curso y se saca de forma unitaria los resultados de aprendizaje
    private function getAllLearningResultsCourse($course)
    {
        $learningResults = [];

        foreach ($course->blocks as $block) {
            foreach ($block->learningResults as $learningResult) {
                $learningResults[$learningResult->uid] = $learningResult->toArray();
            }
        }

        return $learningResults;
    }

    private function refreshToken()
    {
        $generalOptions = app('general_options');

        $params = [
            'grant_type' => 'password',
            'client_id' => $generalOptions['certidigital_client_id'],
            'client_secret' => $generalOptions['certidigital_client_secret'],
            'username' => $generalOptions['certidigital_username'],
            'password' => $generalOptions['certidigital_password'],
        ];

        $certidigitalUrlToken = $generalOptions['certidigital_url_token'];
        $response = Http::asForm()
            ->withoutVerifying()
            ->post($certidigitalUrlToken, $params);

        if ($response->status() != 200) {
            throw new \Exception('Error refreshing Certidigital token');
        }

        session()->put('certidigital_token', $response->json()['access_token']);
        session()->put('certidigital_token_expires', now()->addSeconds($response->json()['expires_in']));

        return $response->json()['access_token'];
    }

    private function createAchievement($name, $achievementChilds = null, $learningResults = null, $assesments = null, $activities = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"

                    ]
                ]
            ],
        ];

        if ($achievementChilds) {
            $data['relSubAchievements'] = [
                "oid" => array_column($achievementChilds, "oid")
            ];
        }

        if ($learningResults) {
            $data['relLearningOutcomes'] = [
                "oid" => array_column($learningResults, "oid")
            ];
        }

        if ($activities) {
            $data['relInfluencedBy'] = [
                "oid" => array_column($activities, "oid")
            ];
        }

        $response = $this->sendRequest($endpoint, $data);
        $ahievementData = $response->json();

        CertidigitalAchievementsModel::create([
            'uid' => $ahievementData['ocbid'],
            'id' => $ahievementData['oid'],
            'title' => $name
        ]);

        if ($achievementChilds) {
            CertidigitalAchievementsModel::whereIn('uid', array_column($achievementChilds, "ocbid"))->update(['certidigital_achievement_uid' => $ahievementData['ocbid']]);
        }

        if ($learningResults) {
            CertidigitalLearningOutcomesModel::whereIn('uid', array_column($learningResults, "ocbid"))->update(['certidigital_achievement_uid' => $ahievementData['ocbid']]);
        }

        if ($activities) {
            CertidigitalActivitiesModel::whereIn('uid', array_column($activities, "ocbid"))->update(['certidigital_achievement_uid' => $ahievementData['ocbid']]);
        }

        if ($assesments) {
            foreach ($assesments as $assesment) {
                $this->vinculateAssesmentToAchievement($assesment, $ahievementData);
            }
        }

        return $response->json();
    }

    private function createActivity($activityName)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/activities?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $activityName,
                        "language" => "es"

                    ]
                ]
            ],
        ];

        $response = $this->sendRequest($endpoint, $data);

        CertidigitalActivitiesModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $activityName
        ]);

        return $response->json();
    }

    private function createAssesment($name, $courseBlockUid = null, $learningResultUid = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/assessments?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"
                    ]
                ]
            ]
        ];

        $response = $this->sendRequest($endpoint, $data);

        CertidigitalAssesmentsModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $name,
            'course_block_uid' => $courseBlockUid,
            'learning_result_uid' => $learningResultUid
        ]);

        return $response->json();
    }

    private function createLearningOutcome($learningResultName, $learningResultOriginCode)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/learning-outcomes?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $learningResultName,
                        "language" => "es"
                    ]
                ]
            ],
        ];

        if ($learningResultOriginCode) {
            $data['relatedESCOSkill'] = [
                [
                    "uri" => $learningResultOriginCode,
                    "targetName" => [
                        "contents" => [
                            [
                                "content" => $learningResultName,
                                "language" => "es",
                                "format" => "text/plain"
                            ]
                        ]
                    ],
                ]
            ];
        }

        $response = $this->sendRequest($endpoint, $data);

        CertidigitalLearningOutcomesModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $learningResultName
        ]);

        return $response->json();
    }

    private function createCredential($name, $description = null, $achievements = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/credentials?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"
                    ]
                ]
            ],
            "validFrom" => now()->toIso8601String(),
            "validUntil" => null,
            "type" => [
                "uri" => "http://data.europa.eu/snb/credential/e34929035b",
                "targetName" => [
                    "contents" => [
                        [
                            "content" => "Genérica",
                            "language" => "es",
                            "format" => "text/plain"
                        ]
                    ]
                ],
                "targetDescription" => null,
                "targetFrameworkURI" => "http://data.europa.eu/snb/credential/25831c2",
                "targetNotation" => null,
                "targetFramework" => null
            ],
        ];

        if ($description) {
            $data['description'] = [
                "contents" => [
                    [
                        "content" => $description,
                        "language" => "es"
                    ]
                ]
            ];
        }

        if ($achievements) {
            $data['relAchieved'] = [
                "oid" => array_column($achievements, "oid")
            ];
        }

        $response = $this->sendRequest($endpoint, $data, true);

        CertidigitalCredentialsModel::create([
            'uid' => $response->json()['ocbid'],
            'id' => $response->json()['oid'],
            'title' => $name
        ]);

        if ($achievements) {
            CertidigitalAchievementsModel::whereIn('uid', array_column($achievements, "ocbid"))->update(['certidigital_credential_uid' => $response->json()['ocbid']]);
        }

        return $response->json();
    }

    private function sendRequest($endpoint, $data, $addCommonFields = true, $method = 'POST')
    {
        $token = $this->getValidToken();

        if ($addCommonFields) {
            // Añadir al data los datos por defecto
            $data['defaultLanguage'] = 'es';
            $data['additionalInfo'] = [
                "languages" => [
                    "es"
                ]
            ];

            $data["relAwardingBody"] = [
                "oid" => [app('general_options')['certidigital_organization_oid']]
            ];
        }

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->$method($endpoint, $data);

        if (!in_array($response->status(), [200, 201])) {
            throw new Exception('Error in the request to Certidigital');
        }

        return $response;
    }
}
