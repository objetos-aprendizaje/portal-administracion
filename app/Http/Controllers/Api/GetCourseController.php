<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use Illuminate\Routing\Controller as BaseController;

class GetCourseController extends BaseController
{
    public function index($courseLmsUid)
    {
        $course = CoursesModel::where("course_lms_uid", $courseLmsUid)->with(['teachers', 'students', 'status', 'center', 'educational_program', 'educational_program.students'])->first();

        if(!$course) throw new OperationFailedException("Curso no encontrado", 404);

        $response = [
            "uid" => $course->uid,
            "uid_lms" => $course->course_lms_uid,
            "status" => $course->status->code,
            "title" => $course->title,
            "description" => $course->description,
            "center" => $course->center ? $course->center->name : null,
            "ects_workload" => $course->ects_workload,
            "lms_url" => $course->lms_url,
            "realization_start_date" => $course->realization_start_date,
            "realization_finish_date" => $course->realization_finish_date,
            "teachers" => $course->teachers->map(function($teacher) {
                return [
                    $teacher->email
                ];
            })
        ];

        if($course->belongs_to_educational_program) {
            $response["students"] = $course->educational_program->students->map(function($student) {
                return [
                    "email" => $student->email,
                    "acceptance_status" => $student->educational_program_student_info->acceptance_status,
                    "status" => $student->educational_program_student_info->status,
                ];
            });
        } else {
            $response["students"] = $course->students->map(function($student) {
                return [
                    "email" => $student->email,
                    "acceptance_status" => $student->course_student_info->acceptance_status,
                    "status" => $student->course_student_info->status,
                ];
            });
        }

        return response()->json($response, 200);
    }

}
