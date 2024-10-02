<?php

namespace App\Http\Controllers;

use App\Services\EmbeddingsService;
use App\Models\CoursesModel;
use App\Models\EducationalResourcesModel;
use Illuminate\Http\Request;

class EmbeddingsTestController extends Controller {
    protected $embeddingsService;

    public function __construct(EmbeddingsService $embeddingsService) {
        $this->embeddingsService = $embeddingsService;
    }

    // Regenerate specific course embeddings
    public function regenerateEmbeddings($uid) {
        $course = CoursesModel::where('uid', $uid)->first();
        if (!$course) {
            return response()->json(['error' => 'Course not found.'], 404);
        }

        $this->embeddingsService->generateEmbeddingForCourse($course);

        return response()->json(['message' => 'Course embeddings have been regenerated.']);
    }

    // Regenerate all course embeddings
    public function regenerateAllEmbeddings() {
        $courses = CoursesModel::all();

        foreach ($courses as $course) {
            $this->embeddingsService->generateEmbeddingForCourse($course);
        }

        $educationalResources = EducationalResourcesModel::all();
        foreach ($educationalResources as $educationalResource) {
            $this->embeddingsService->generateEmbeddingForEducationalResource($educationalResource);
        }

        return response()->json(['message' => 'All course embeddings have been regenerated.']);
    }

    // Get similar courses based on a list of provided course UIDs
    public function getSimilarCourses(Request $request) {
        // $courseUids = $request->input('course_uids', []);
        // if (empty($courseUids)) {
        //     return response()->json(['error' => 'No courses provided.'], 400);
        // }

        // $courses = CoursesModel::whereIn('uid', $courseUids)->get();
        $courses = CoursesModel::whereIn('uid', ['779b17fc-b0e5-46e7-a41a-27d91611cc06'])->get();

        if ($courses->isEmpty()) {
            return response()->json(['error' => 'No courses found.'], 404);
        }

        $similarCourses = $this->embeddingsService->getSimilarCoursesList($courses);

        return response()->json($similarCourses->map(function ($item) {
            return collect($item)->only(['uid', 'title', 'description']);
        }));
    }

    public function getSimilarCoursesOfSingleCourse($uid) {
        $course = CoursesModel::where('uid', $uid)->first();
        if (!$course) {
            return response()->json(['error' => 'Course not found.'], 404);
        }


        $similarCourses = $this->embeddingsService->getSimilarCourses($course);

        return response()->json($similarCourses->map(function ($item) {
            return collect($item)->only(['uid', 'title', 'description']);
        }));
    }
}
