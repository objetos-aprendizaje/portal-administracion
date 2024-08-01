<?php

namespace App\Http\Controllers\LearningObjects;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LearningObjetsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function generateTags(Request $request)
    {
        $text = $request->input("text");

        $text = str_replace("\u{200B}", "", $text);

        $openaiKey = app('general_options')['openai_key'];

        $data = [
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that generate tags for a description. You always return the tags in a single array json. YOU CANT RETURN ANY OTHER STRUCTURE. Tags must be useful for SEO and relevant for the description. Max 10 tags.'],
                ['role' => 'user', 'content' => "This is the text: $text"]
            ],
        ];

        $header = [
            'Authorization: Bearer ' . $openaiKey,
            'Content-Type: application/json'
        ];

        $response = curl_call('https://api.openai.com/v1/chat/completions', json_encode($data), $header, 'POST');

        $responseData = json_decode($response, true);

        $tags = json_decode($responseData['choices'][0]['message']['content'], true);

        return response()->json($tags, 200);
    }

}
