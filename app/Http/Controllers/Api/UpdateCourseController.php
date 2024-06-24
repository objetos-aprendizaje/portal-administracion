<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UpdateCourseController extends BaseController
{
    public function index(Request $request)
    {
        $updateData = $request->all();
        $errorsValidation = $this->validate($updateData);

        if ($errorsValidation) {
            return response()->json(['errors' => $errorsValidation], 400);
        }

        $this->validateCourseDates($updateData);
        $this->updateCourse($updateData);

        return response()->json(['message' => 'Curso actualizado correctamente'], 200);
    }

    private function updateCourse($updateData)
    {
        CoursesModel::where("course_lms_uid", $updateData["lms_uid"])->update([
            "title" => $updateData["title"],
            "description" => $updateData["description"],
            "lms_url" => $updateData["lms_url"],
            "ects_workload" => $updateData["ects_workload"],
            "realization_start_date" => $updateData["realization_start_date"],
            "realization_finish_date" => $updateData["realization_finish_date"],
        ]);
    }

    private function validateCourseDates($updateData) {

        $course = CoursesModel::where("course_lms_uid", $updateData["lms_uid"])->with("educational_program")->first();

        if($course->belongs_to_educational_program) {
            $this->validationCourseBelongsToEducationalProgram($updateData, $course);
        } else {
            $this->validationCourseNotBelongsToEducationalProgram($updateData, $course);
        }
    }

    private function validationCourseBelongsToEducationalProgram($updateData, $course) {
        $educationalProgram = $course->educational_program;

        if($educationalProgram->validate_student_registrations || $educationalProgram->cost && $educationalProgram->cost > 0) {
            if($updateData["realization_start_date"] < $educationalProgram->enrolling_finish_date) {
                throw new OperationFailedException("La fecha de inicio del curso no puede ser anterior a la fecha de fin de matriculación del programa formativo", 406);
            }
        } else {
            if($updateData["realization_start_date"] < $educationalProgram->inscription_finish_date) {
                throw new OperationFailedException("La fecha de inicio de realización del curso no puede ser anterior a la fecha de fin de inscripción del programa formativo", 406);
            }
        }
    }

    // Validamos que la fecha de realización sea posterior a la de inscripción o matriculación
    private function validationCourseNotBelongsToEducationalProgram($updateData, $course) {
        if($course->validate_student_registrations || $course->cost && $course->cost > 0) {
            if($updateData["realization_start_date"] < $course->enrolling_finish_date) {

                throw new OperationFailedException("La fecha de inicio del curso no puede ser anterior a la fecha de fin de matriculación", 406);
            }
        } else {
            if($updateData["realization_start_date"] < $course->inscription_finish_date) {
                throw new OperationFailedException("La fecha de inicio de realización del curso no puede ser anterior a la fecha de fin de inscripción", 406);
            }
        }
    }

    private function validate($updateData)
    {
        $messages = [
            "lms_uid.required" => "El campo lms_uid es obligatorio",
            "lms_uid.string" => "El campo lms_uid debe ser un string",
            "lms_uid.unique" => "Ya existe un curso con el uid de LMS proporcionado",
            "title.string" => "El campo title debe ser un string",
            "description.string" => "El campo description debe ser un string",
            "lms_url.url" => "El campo lms_url debe ser una URL válida",
            "ects_workload.integer" => "El campo ects_workload debe ser un entero",
            "realization_start_date.date" => "El campo realization_start_date debe ser una fecha",
            "realization_end_date.date" => "El campo realization_end_date debe ser una fecha",
            "realization_start_date.date_format" => "El campo realization_start_date debe tener el formato Y-m-d H:i:s. Ejemplo: 2021-12-31 23:59:00",
            "realization_finish_date.date_format" => "El campo realization_end_date debe tener el formato Y-m-d H:i:s. Ejemplo: 2021-12-31 23:59:00",
            "realization_finish_date.after" => "El campo realization_end_date debe ser una fecha posterior a realization_start_date",
        ];

        $rules = [
            "lms_uid" => "required|string|exists:courses,course_lms_uid",
            "title" => "required|string",
            "description" => "required|nullable",
            "lms_url" => "required|url",
            "ects_workload" => "required|integer",
            "realization_start_date" => "required|date_format:Y-m-d H:i:s",
            "realization_finish_date" => "required|date_format:Y-m-d H:i:s",
            "realization_start_date" => "after_or_equal:now",
            "realization_finish_date" => "after:realization_start_date"
        ];

        $validator = Validator::make($updateData, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors();
        }
    }
}
