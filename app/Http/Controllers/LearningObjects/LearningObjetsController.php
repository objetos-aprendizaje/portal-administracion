<?php

namespace App\Http\Controllers\LearningObjects;

use App\Exceptions\OperationFailedException;
use App\Services\CertidigitalService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LearningObjetsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $certidigitalService;

    public function __construct(CertidigitalService $certidigitalService)
    {
        $this->certidigitalService = $certidigitalService;
    }

    public function generateTags(Request $request)
    {
        $text = $this->getText($request);

        $data = [
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente que genera etiquetas para una descripción. Siempre devuelves las etiquetas en un único array json. NO PUEDES DEVOLVER NINGUNA OTRA ESTRUCTURA. Las etiquetas deben ser útiles para SEO y relevantes para la descripción. Máximo 10 etiquetas.'],
                ['role' => 'user', 'content' => "This is the text: $text"]
            ],
        ];

        $tags = $this->callOpenAI($data);

        return response()->json($tags, 200);
    }

    public function generateMetadata(Request $request)
    {

        $text = $this->getText($request);

        $data = [
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
                        Genera un array JSON de clave-valor de metadatos útiles para SEO y relevantes para un texto que
                        te pase el usuario. El array debe tener la estructura clave-valor y no debe contener ninguna otra
                        estructura. Los metadatos deben ser libres y relevantes para la descripción proporcionada.
                        Este artículo explora las mejores prácticas para el desarrollo web moderno, incluyendo el uso de
                        frameworks populares como React y Vue.js, así como técnicas avanzadas de optimización de rendimiento.
                        El array JSON debe tener la siguiente estructura:
                        [
                            {
                                "key": "aquí la clave",
                                "value": "aquí el valor",
                            }
                            ...
                        ]
                        IMPORTANTE: Siempre debe devolver texto.'
                ],
                [
                    'role' => 'user',
                    'content' => "Este es el texto: $text"
                ]
            ],
        ];

        $metadata = $this->callOpenAI($data);

        return response()->json($metadata, 200);
    }

    private function getText($request) {
        $text = $request->input("text");
        $text = str_replace("\u{200B}", "", $text);
        return $text;
    }

    public function getUrlEmissionCredential($emissionsBlockUuid) {
        if(!$emissionsBlockUuid) {
            throw new OperationFailedException('No se ha enviado el uuid del bloque de emisiones', 406);
        }

        $url = $this->certidigitalService->getEmissionCredential($emissionsBlockUuid);
        return response()->json(['url' => $url], 200);
    }

    private function callOpenAI($data)
    {
        $openaiKey = app('general_options')['openai_key'];

        $header = [
            'Authorization: Bearer ' . $openaiKey,
            'Content-Type: application/json'
        ];

        $response = curlCall('https://api.openai.com/v1/chat/completions', json_encode($data), $header, 'POST');

        $responseData = json_decode($response, true);

        return json_decode($responseData['choices'][0]['message']['content'], true);
    }
}
