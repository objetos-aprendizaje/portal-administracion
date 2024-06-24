<?php

namespace App\Http\Controllers\Administration;

use App\Models\CoursesBigCarrouselsApprovalsModel;
use App\Models\CoursesModel;
use App\Models\CoursesSmallCarrouselsApprovalsModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\SlidersPrevisualizationsModel;
use Illuminate\Support\Facades\Validator;

class CarrouselsController extends BaseController
{
    public function index()
    {
        $courses_big_carrousel = CoursesModel::where('featured_big_carrousel', true)
            ->whereHas('status', function ($query) {
                $query->where('code', '=', 'INSCRIPTION');
            })
            ->select('uid', 'title')->get();

        $courses_small_carrousel = CoursesModel::where('featured_small_carrousel', true)->whereHas('status', function ($query) {
            $query->where('code', '=', 'INSCRIPTION');
        })->select('uid', 'title')->get();

        $courses_big_carrousel_approved = CoursesBigCarrouselsApprovalsModel::pluck('course_uid')->toArray();
        $courses_small_carrousel_approved = CoursesSmallCarrouselsApprovalsModel::pluck('course_uid')->toArray();

        return view('administration.carrousels.index', [
            "page_name" => "Slider y carrousel principal",
            "page_title" => "Slider y carrousel principal",
            "resources" => [
                "resources/js/administration_module/carrousels.js"
            ],
            "courses_big_carrousel" => $courses_big_carrousel,
            "courses_small_carrousel" => $courses_small_carrousel,
            "courses_big_carrousel_approved" => $courses_big_carrousel_approved,
            "courses_small_carrousel_approved" => $courses_small_carrousel_approved,
            "submenuselected" => "carrousels",
        ]);
    }

    public function previsualizeSlider(Request $request) {

        $rules = [
            'title' => 'required',
            'description' => 'required',
            'image' => 'required',
        ];

        $messages = [
            'title.required' => 'Debes especificar un título',
            'description.required' => 'Debes especificar una descripción',
            'image.required' => 'Debes adjuntar una imagen',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Guardamos la imagen
        $image = $request->file('image');

        $imagePath = saveFile($image, "images/previsualizations-sliders", null, true);

        $previsualizationSlider = new SlidersPrevisualizationsModel();
        $previsualizationSlider->uid = generate_uuid();
        $previsualizationSlider->fill($request->all());
        $previsualizationSlider->image_path = $imagePath;

        $previsualizationSlider->save();

        return response()->json([
            'message' => 'Se ha guardado la previsualización del slider',
            'previsualizationUid' => $previsualizationSlider->uid
        ]);
    }

    public function save_big_carrousels_approvals(Request $request)
    {
        DB::transaction(function () use ($request) {
            $this->save_carrousels_approvals($request, CoursesBigCarrouselsApprovalsModel::class);
            LogsController::createLog('Actualizar carrouseles grandes', 'Administración carrouseles', auth()->user()->uid);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Se han actualizado los cursos a mostrar en el carrousel grande'
        ]);
    }

    public function save_small_carrousels_approvals(Request $request)
    {
        DB::transaction(function () use ($request) {
            $this->save_carrousels_approvals($request, CoursesSmallCarrouselsApprovalsModel::class);
            LogsController::createLog('Actualizar carrouseles pequeños', 'Administración carrouseles', auth()->user()->uid);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Se han actualizado los cursos a mostrar en el carrousel pequeño'
        ]);
    }

    private function save_carrousels_approvals(Request $request, $model)
    {
        DB::transaction(function () use ($request, $model) {
            $newCourseUids = $request->input('courses');

            $currentCourseUids = $model::pluck('course_uid')->toArray();

            $courseUidsToInsert = array_diff($newCourseUids, $currentCourseUids);
            $courseUidsToDelete = array_diff($currentCourseUids, $newCourseUids);

            $recordsToInsert = array_map(function ($courseUid) {
                return [
                    'uid' => (string) generate_uuid(),
                    'course_uid' => $courseUid
                ];
            }, $courseUidsToInsert);

            $model::insert($recordsToInsert);

            $model::whereIn('course_uid', $courseUidsToDelete)->delete();
        });
    }
}
