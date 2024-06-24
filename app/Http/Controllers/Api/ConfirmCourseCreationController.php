<?php
namespace App\Http\Controllers\Api;

use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ConfirmCourseCreationController extends BaseController
{
    public function index(Request $request)
    {
        $courseConfirm = $request->all();

        $errorsValidation = $this->validateCourseConfirms($courseConfirm);

        if($errorsValidation) {
            return response()->json(['errors' => $errorsValidation], 400);
        }

        $this->saveCourseConfirm($courseConfirm);

        return response()->json(['message' => 'Curso confirmado correctamente'], 200);
    }

    private function saveCourseConfirm($courseConfirm) {
        CoursesModel::where("uid", $courseConfirm["poa_uid"])->update([
            "course_lms_uid" => $courseConfirm["lms_uid"],
            "lms_url" => $courseConfirm["lms_url"]
        ]);
    }

    private function validateCourseConfirms($courseConfirm) {

        $messages = [
            "lms_uid.required" => "El campo lms_uid es obligatorio",
            "lms_uid.string" => "El campo lms_uid debe ser un string",
            "lms_uid.unique" => "Ya existe un curso con el uid de LMS proporcionado",
            "poa_uid.required" => "El campo poa_uid es obligatorio",
            "poa_uid.string" => "El campo poa_uid debe ser un string",
            "poa_uid.exists" => "No existe un curso con el uid de POA proporcionado",
            "lms_url.required" => "El campo lms_url es obligatorio",
            "lms_url.url" => "El campo lms_url debe ser una URL válida"
        ];

        $rules = [
            "lms_uid" => "required|string|unique:courses,course_lms_uid",
            "poa_uid" => "required|string|exists:courses,uid",
            "lms_url" => "required|url"
        ];

        $validator = Validator::make($courseConfirm, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors();
        }

    }

}
