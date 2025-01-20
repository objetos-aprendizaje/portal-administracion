<?php

namespace App\Http\Controllers\Administration;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\EducationalProgramsModel;
use App\Models\SlidersPrevisualizationsModel;
use Illuminate\Support\Facades\Validator;

class CarrouselsController extends BaseController
{
    public function index()
    {
        $coursesSlider = CoursesModel::where('featured_big_carrousel', true)
            ->with("status")
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['ACCEPTED_PUBLICATION', 'INSCRIPTION']);
            })
            ->get();

        $educationalProgramsSlider = EducationalProgramsModel::where('featured_slider', true)
            ->with("status")
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['ACCEPTED_PUBLICATION', 'INSCRIPTION']);
            })
            ->get();

        $coursesCarrousel = CoursesModel::where('featured_small_carrousel', true)
            ->with("status")
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['ACCEPTED_PUBLICATION', 'INSCRIPTION']);
            })->get();

        $educationalProgramsCarrousel = EducationalProgramsModel::where('featured_main_carrousel_approved', true)
            ->with("status")
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['ACCEPTED_PUBLICATION', 'INSCRIPTION']);
            })
            ->get();


        return view('administration.carrousels.index', [
            "page_name" => "Slider y carrousel principal",
            "page_title" => "Slider y carrousel principal",
            "resources" => [
                "resources/js/administration_module/carrousels.js"
            ],
            "coursesSlider" => $coursesSlider,
            "educationalProgramsSlider" => $educationalProgramsSlider,
            "coursesCarrousel" => $coursesCarrousel,
            "educationalProgramsCarrousel" => $educationalProgramsCarrousel,
            "submenuselected" => "carrousels",
        ]);
    }

    public function previsualizeSlider(Request $request)
    {
        $this->validatePrevisualizationSlider($request);

        // Si no viene adjunta una imagen, se toma la que está en BD. Si no hay ninguna, se lanza una excepción
        $imagePath = $this->getPrevisualizationImage($request);

        $previsualizationSlider = new SlidersPrevisualizationsModel();
        $previsualizationSlider->uid = generateUuid();
        $previsualizationSlider->fill($request->all());
        $previsualizationSlider->image_path = $imagePath;

        $previsualizationSlider->save();

        return response()->json([
            'message' => 'Se ha guardado la previsualización del slider',
            'previsualizationUid' => $previsualizationSlider->uid
        ]);
    }

    private function getPrevisualizationImage($request)
    {
        $image = $request->file('image');
        $learningObjectType = $request->input('learning_object_type');

        if (!$image) {
            if ($learningObjectType == "course") {
                $course = CoursesModel::where('uid', $request->input('course_uid'))->first();
                $imagePath = $course->featured_big_carrousel_image_path;
            } elseif ($learningObjectType == "educational_program") {
                $educationalProgram = EducationalProgramsModel::where('uid', $request->input('educational_program_uid'))->first();
                $imagePath = $educationalProgram->featured_slider_image_path;
            }
        } else {
            $imagePath = saveFile($image, "images/previsualizations-sliders", null, true);
        }

        if (!$imagePath) {
            throw new OperationFailedException('Debes adjuntar una imagen', 422);
        }

        return $imagePath;
    }

    private function validatePrevisualizationSlider($request)
    {

        $messages = [
            'title.required' => 'Debes especificar un título',
            'description.required' => 'Debes especificar una descripción',
            'image.required' => 'Debes adjuntar una imagen',
            'image.file' => 'Debes adjuntar una imagen válida',
            'color.required' => 'Debes especificar un color',
        ];

        $rules = [
            'title' => 'required',
            'description' => 'required',
            'color' => 'required',
        ];

        $courseUid = $request->input("course_uid");
        $educationalProgramUid = $request->input("educational_program_uid");

        if (!$courseUid && !$educationalProgramUid) {
            $rules['image'] = 'required|file';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new OperationFailedException($validator->errors()->first());
        }
    }

    public function saveBigCarrouselsApprovals(Request $request)
    {
        $courses = $request->input('courses');
        $educationalPrograms = $request->input('educationalPrograms');

        DB::transaction(function () use ($courses, $educationalPrograms) {

            $coursesToEnableUids = $this->filterArrayLearningObjects($courses, true);
            CoursesModel::whereIn('uid', $coursesToEnableUids)->update(['featured_big_carrousel_approved' => true]);

            $coursesToDisableUids = $this->filterArrayLearningObjects($courses, false);
            CoursesModel::whereIn('uid', $coursesToDisableUids)->update(['featured_big_carrousel_approved' => false]);

            $educationalProgramsToEnableUids = $this->filterArrayLearningObjects($educationalPrograms, true);
            EducationalProgramsModel::whereIn('uid', $educationalProgramsToEnableUids)->update(['featured_slider_approved' => true]);

            $educationalProgramsToDisableUids = $this->filterArrayLearningObjects($educationalPrograms, false);
            EducationalProgramsModel::whereIn('uid', $educationalProgramsToDisableUids)->update(['featured_slider_approved' => false]);

            LogsController::createLog('Actualizar slider principal', 'Administración carrouseles', auth()->user()->uid);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Se han actualizado los cursos a mostrar en el carrousel grande'
        ]);
    }

    public function saveSmallCarrouselsApprovals(Request $request)
    {
        $courses = $request->input('courses');
        $educationalPrograms = $request->input('educationalPrograms');


        DB::transaction(function () use ($courses, $educationalPrograms) {

            $coursesToEnableUids = $this->filterArrayLearningObjects($courses, true);
            CoursesModel::whereIn('uid', $coursesToEnableUids)->update(['featured_small_carrousel_approved' => true]);

            $coursesToDisableUids = $this->filterArrayLearningObjects($courses, false);
            CoursesModel::whereIn('uid', $coursesToDisableUids)->update(['featured_small_carrousel_approved' => false]);

            $educationalProgramsToEnableUids = $this->filterArrayLearningObjects($educationalPrograms, true);
            EducationalProgramsModel::whereIn('uid', $educationalProgramsToEnableUids)->update(['featured_main_carrousel_approved' => true]);

            $educationalProgramsToDisableUids = $this->filterArrayLearningObjects($educationalPrograms, false);
            EducationalProgramsModel::whereIn('uid', $educationalProgramsToDisableUids)->update(['featured_main_carrousel_approved' => false]);

            LogsController::createLog('Actualizar carrouseles principal', 'Administración carrouseles', auth()->user()->uid);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Se han actualizado los cursos a mostrar en el carrousel pequeño'
        ]);
    }

    private function filterArrayLearningObjects($array, $checked)
    {
        return array_map(function ($learningObject) {
            return $learningObject['uid'];
        }, array_filter($array, function ($learningObject) use ($checked) {
            return $learningObject['checked'] == $checked;
        }));
    }
}
