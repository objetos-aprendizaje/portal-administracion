<?php

namespace App\Services;

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

    public function updateCourseCredential($courseBd)
    {
        $token = $this->getValidToken();

        $courseBd->load('blocks.learningResults', 'blocks.subBlocks.elements.subElements');

        $learningResultsBlocksStructure = $this->getLearningResultsBlocksStructure($courseBd->blocks);


        $fatherAchievements[] = [];
        foreach ($learningResultsBlocksStructure as $learningResultBlock) {
            // Creamos los resultados

            $achievementsLearningResult = [];
            foreach ($learningResultBlock['blocks'] as $block) {
                $achievementsLearningResult[] = $this->createAchievement($block['name']);
            }

            $learningResult = $this->createLearningResult($learningResultBlock['dataLearningResult']);
            $fatherAchievements[] = $this->createAchievement($learningResultBlock['dataLearningResult']['name'], $achievementsLearningResult, [$learningResult]);
        }

        dd("llega", $fatherAchievements);

        dd($learningResultsBlocksStructure);

        $mainAchievement = $this->createAchievement($courseBd->title);

        dd($mainAchievement);

        $activities = [];
        $learningResults = [];
        foreach ($courseBd->blocks as $block) {
            //$activities[] = $this->createActivity($block);

            foreach ($block->learningResults as $learningResult) {
                //$learningResults[] = $this->createLearningResult($learningResult);
            }
        }

        //$assessmentOid = $this->createAssesment($courseBd);

        // dd($assessmentOid);
    }

    private function getLearningResultsBlocksStructure($blocks)
    {
        $learningResults = [];

        // Primera iteración para construir el array de resultados de aprendizaje únicos
        foreach ($blocks as $block) {
            foreach ($block->learningResults as $learningResult) {
                $uid = $learningResult['uid'];
                if (!isset($learningResults[$uid])) {
                    $learningResults[$uid] = [
                        'dataLearningResult' => $learningResult->toArray(),
                        'blocks' => []
                    ];
                }
            }
        }

        // Segunda iteración para agregar la información de los bloques a cada resultado de aprendizaje
        foreach ($blocks as $block) {
            foreach ($block->learningResults as $learningResult) {
                $uid = $learningResult['uid'];
                if (isset($learningResults[$uid])) {
                    $learningResults[$uid]['blocks'][] = $block->toArray();
                }
            }
        }

        return $learningResults;
    }

    private function createAchievement($name, $achievementChilds = null, $learningResults = null)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/achievements?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            'defaultLanguage' => 'es',
            "title" => [
                "contents" => [
                    [
                        "content" => $name,
                        "language" => "es"

                    ]
                ]
            ],
            "relAwardingBody" => [
                "oid" => [29]
            ],
            "additionalInfo" => [
                "languages" => [
                    "es"
                ]
            ]
        ];

        if ($achievementChilds) {
            $data['relSubAchievements'] = [
                "oid" => $achievementChilds
            ];
        }

        if($learningResults) {
            $data['relLearningOutcomes'] = [
                "oid" => $learningResults
            ];
        }

        $token = $this->getValidToken();
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post($endpoint, $data);

        $oidAchievement = $response->json()['oid'];

        return $oidAchievement;
    }

    private function createActivity($block)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/activities?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $params = [
            'defaultLanguage' => 'es',
            "title" => [
                "contents" => [
                    [
                        "content" => $block->name,
                        "language" => "es"

                    ]
                ]
            ],
            "relAwardingBody" => [
                "oid" => [29]
            ],
            "additionalInfo" => [
                "languages" => [
                    "es"
                ]
            ],
        ];

        $token = $this->getValidToken();

        // envío api
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post($endpoint, $params);

        $oidActivity = $response->json()['oid'];

        return $oidActivity;
    }

    private function createAssesment($course)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/assessments?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            'defaultLanguage' => 'es',
            "title" => [
                "contents" => [
                    [
                        "content" => "Evaluación curso " . $course->title,
                        "language" => "es"
                    ]
                ]
            ],
            "specifiedBy" => [
                "title" => [
                    "contents" => [
                        [
                            "content" => "Nombre de la evaluación",
                            "language" => "es"
                        ]
                    ]
                ]
            ],
            "additionalInfo" => [
                "languages" => [
                    "es"
                ]
            ],
            "relAwardingBody" => [
                "oid" => [
                    29
                ]
            ],
        ];

        $token = $this->getValidToken();
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post($endpoint, $data);

        $assesmentOid = $response->json()['oid'];
        return $assesmentOid;
    }

    private function createLearningResult($learningResult)
    {
        $generalOptions = app('general_options');
        $endpoint = "{$generalOptions['certidigital_url']}/certi-bridge/api/v1/learning-outcomes?issuingCenterId={$generalOptions['certidigital_center_id']}";

        $data = [
            'defaultLanguage' => 'es',
            "title" => [
                "contents" => [
                    [
                        "content" => $learningResult['name'],
                        "language" => "es"
                    ]
                ]
            ],
            "additionalInfo" => [
                "languages" => [
                    "es"
                ]
            ],
        ];

        if ($learningResult['origin_code']) {
            $data['relatedESCOSkill'] = [
                [
                    "uri" => $learningResult['origin_code'],
                    "targetName" => [
                        "contents" => [
                            [
                                "content" => $learningResult['name'],
                                "language" => "es",
                                "format" => "text/plain"
                            ]
                        ]
                    ],
                ]
            ];
        }

        $token = $this->getValidToken();
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->post($endpoint, $data);

        $learningResultOid = $response->json()['oid'];
        return $learningResultOid;
    }
}
