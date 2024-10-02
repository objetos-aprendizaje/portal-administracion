<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmbeddingsTestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::get('/embeddings/regenerate/{uid}', [EmbeddingsTestController::class, 'regenerateEmbeddings']);
Route::get('/embeddings/regenerate-all', [EmbeddingsTestController::class, 'regenerateAllEmbeddings']);
Route::get('/embeddings/similar-courses/{uid}', [EmbeddingsTestController::class, 'getSimilarCoursesOfSingleCourse']);
Route::get('/embeddings/similar-courses', [EmbeddingsTestController::class, 'getSimilarCourses']);
Route::post('/embeddings/similar-courses', [EmbeddingsTestController::class, 'getSimilarCourses']);

