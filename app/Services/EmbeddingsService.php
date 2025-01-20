<?php

namespace App\Services;

use App\Models\CoursesEmbeddingsModel;
use App\Models\CoursesModel;
use App\Models\EducationalResourcesEmbeddingsModel;
use App\Models\EducationalResourcesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingsService
{
    private $openAiApiKey;

    public function __construct()
    {
        $openaiKey = GeneralOptionsModel::where('option_name', 'openai_key')->first();
        $this->openAiApiKey = $openaiKey ? $openaiKey['option_value'] : null;
    }

    public function getEmbedding($text)
    {
        if(!app('general_options')['enabled_recommendation_module']) {
            return null;
        }

        if (!$this->openAiApiKey) {
            Log::error('OpenAI API key not found.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openAiApiKey,
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);
            return $response->json()['data'][0]['embedding'];
        } catch (\Exception $e) {
            Log::error("Error generating embedding for text: $text");
            return null;
        }
    }

    public function generateEmbeddingForCourse(CoursesModel $course)
    {
        $text = $course->title . ' ' . $course->description;

        $embeddings = $this->getEmbedding($text);
        Log::info('Embedding generated for course ' . $course->uid. ': ' . json_encode($embeddings));

        if ($embeddings) {
            CoursesEmbeddingsModel::updateOrCreate(
                ['course_uid' => $course->uid],
                ['embeddings' => $embeddings]
            );
        }
    }

    public function generateEmbeddingForEducationalResource(EducationalResourcesModel $educationalResource)
    {
        $text = $educationalResource->title . ' ' . $educationalResource->description;
        $embedding = $this->getEmbedding($text);
        Log::error('Embedding generated for educational resource ' . $educationalResource->uid . ': ' . json_encode($embedding));

        // Update the educational resource with the new embedding
        if ($embedding) {
            EducationalResourcesEmbeddingsModel::updateOrCreate(
                ['educational_resource_uid' => $educationalResource->uid],
                ['embeddings' => $embedding]
            );
        }
    }

    public function getSimilarCourses(CoursesModel $course, $limit = 5)
    {
        $embedding = $course->embeddings;

        return CoursesModel::select('courses.*')
            ->selectRaw('1 - (embeddings <=> ?) AS similarity', [$embedding])
            ->where('embeddings', '!=', null)
            ->where('uid', '!=', $course->uid) // Exclude the current course
            ->orderByDesc('similarity')
            ->limit($limit)
            ->get();
    }

    public function getSimilarCoursesList(Collection $courses, $limit = 5)
    {
        $uids = $courses->map(fn($course) => $course->uid)->toArray();
        $embeddings = $courses->pluck('embeddings')->map(function ($embedding) {
            // Convert the string of embeddings into an array
            return array_map('floatval', explode(',', trim($embedding, '()')));
        })->toArray();

        // Calculate the average embedding by averaging the values for each dimension
        $averageEmbedding = array_reduce($embeddings, function ($carry, $embedding) {
            foreach ($embedding as $index => $value) {
                $carry[$index] = ($carry[$index] ?? 0) + $value;
            }
            return $carry;
        }, []);

        // Divide by the number of embeddings to get the average
        $embeddingCount = count($embeddings);
        foreach ($averageEmbedding as &$value) {
            $value /= $embeddingCount;
        }

        // Convert the average embedding into a PostgreSQL vector string format
        $embeddingVectorString = '[' . implode(',', $averageEmbedding) . ']';

        return CoursesModel::select('courses.*')
            ->selectRaw('1 - (embeddings <=> ?) AS similarity', [$embeddingVectorString])
            ->where('embeddings', '!=', null)
            ->whereNotIn('uid', $uids)
            ->orderByDesc('similarity')
            ->limit($limit)
            ->get();

    }

    // Regenerate all embeddings
    public function regenerateAllEmbeddings()
    {
        $courses = CoursesModel::all();

        foreach ($courses as $course) {
            $this->generateEmbeddingForCourse($course);
        }

        $educationalResources = EducationalResourcesModel::all();
        foreach ($educationalResources as $educationalResource) {
            $this->generateEmbeddingForEducationalResource($educationalResource);
        }
    }
}
