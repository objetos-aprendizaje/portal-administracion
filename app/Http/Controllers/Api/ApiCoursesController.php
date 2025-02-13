<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use App\Models\UsersModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class ApiCoursesController extends BaseController
{
    public function getCourses(Request $request)
    {
        $courseQuery = CoursesModel::with(['teachers', 'center', 'students', 'status', 'center', 'educational_program', 'educational_program.students']);

        $allowFilters = [
            'uid',
            'course_lms_id',
        ];

        $filters = $request->all();

        $filteredFilters = array_filter(
            $filters,
            fn($key) => in_array($key, $allowFilters),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($filteredFilters as $key => $value) {
            $courseQuery->where($key, $value);
        }

        if ($request->has('status')) {
            $courseQuery->whereHas('status', function ($query) use ($request) {
                $query->whereIn('code', $request->status);
            });
        }

        $courses = $courseQuery->get();

        $courses = $courses->map(function ($course) {
            return [
                'uid' => $course->uid,
                'course_lms_id' => $course->course_lms_id,
                'status' => $course->status->code,
                'title' => $course->title,
                'description' => $course->description,
                'center' => $course->center,
                'ects_workload' => $course->ects_workload,
                'lms_url' => $course->lms_url,
                'realization_start_date' => $course->realization_start_date,
                'realization_finish_date' => $course->realization_finish_date,
                'teachers' => $course->teachers->pluck('uid')->toArray(),
                'students' => $course->students->map(function ($student) {
                    return [
                        "uid" => $student->uid,
                        "email" => $student->email,
                        "acceptance_status" => $student->course_student_info->acceptance_status,
                        "status" => $student->course_student_info->status,
                    ];
                })->toArray(),
            ];
        });

        return response()->json($courses, 200);
    }

    public function confirmCourseCreation(Request $request)
    {
        $courseConfirm = $request->all();

        $errorsValidation = $this->validateCourseConfirms($courseConfirm);

        if ($errorsValidation) {
            return response()->json(['errors' => $errorsValidation], 400);
        }

        $this->saveCourseConfirm($courseConfirm);

        return response()->json(['message' => 'Curso confirmado correctamente'], 200);
    }

    public function updateCourse(Request $request, $courseLmsId)
    {
        $course = CoursesModel::where("course_lms_id", $courseLmsId)->with("educational_program")->first();
        if (!$course) {
            throw new OperationFailedException("No existe un curso con el uid de LMS proporcionado", 404);
        }

        DB::transaction(function () use ($request, $course) {
            $teachersRequest = $request['teachers'];
            if (isset($teachersRequest)) {
                $this->updateTeachers($teachersRequest, $course);
            }

            $studentsRequest = $request['students'];
            if (isset($studentsRequest)) {
                $this->updateStudents($studentsRequest, $course);
            }

            $updateData = $request->all();
            $this->updateCourseDb($updateData, $course);
        });

        return response()->json(['message' => 'Curso actualizado correctamente'], 200);
    }

    private function updateStudents($studentsRequest, $course)
    {
        $uidsStudents = [];
        foreach ($studentsRequest as $student) {
            $uidsStudents[] = $student;
        }

        $students = UsersModel::with('roles')->whereIn('uid', $uidsStudents)
            ->get();

        if ($students->count() != count($uidsStudents)) {
            throw new OperationFailedException("Uno o varios de los estudiantes proporcionados no existen", 404);
        }

        foreach ($students as $student) {
            if (!$student->roles->contains('code', 'STUDENT')) {
                throw new OperationFailedException("Uno o varios de los estudiantes proporcionados no tienen el rol de estudiante", 404);
            }
        }

        $studentsToSync = [];
        foreach ($uidsStudents as $studentUid) {
            $studentsToSync[$studentUid] = [
                'uid' => generateUuid(),
                'course_uid' => $course->uid,
                'user_uid' => $studentUid
            ];
        }

        $course->students()->sync($studentsToSync);
    }

    private function updateTeachers($teachersData, $course)
    {
        $this->validateTeachersData($teachersData);

        $uidsTeachers = $this->extractTeacherUids($teachersData);

        $teachers = $this->validateTeachersExistence($uidsTeachers);

        $this->validateTeachersRoles($teachers);

        $teachersToSync = $this->prepareTeachersToSync($uidsTeachers, $teachersData, $course);

        $course->teachers()->sync($teachersToSync);
    }

    private function validateTeachersData($teachersData)
    {
        if ($teachersData['coordinator'] && $teachersData['no_coordinator']) {
            $intersect = array_intersect($teachersData['coordinator'], $teachersData['no_coordinator']);
            if ($intersect) {
                throw new OperationFailedException("Un profesor no puede ser coordinador y no coordinador a la vez", 406);
            }
        }
    }

    private function extractTeacherUids($teachersData)
    {
        $uidsTeachers = [];
        if ($teachersData['coordinator']) {
            foreach ($teachersData['coordinator'] as $teacher) {
                $uidsTeachers[] = $teacher;
            }
        }

        if (isset($teachersData['no_coordinator'])) {
            foreach ($teachersData['no_coordinator'] as $teacher) {
                $uidsTeachers[] = $teacher;
            }
        }

        return $uidsTeachers;
    }

    private function validateTeachersExistence($uidsTeachers)
    {
        $teachers = UsersModel::with('roles')->whereIn('uid', $uidsTeachers)->get();

        if ($teachers->count() != count($uidsTeachers)) {
            throw new OperationFailedException("Uno o varios de los profesores proporcionados no existen o no tienen el rol de profesor", 404);
        }

        return $teachers;
    }

    private function validateTeachersRoles($teachers)
    {
        foreach ($teachers as $teacher) {
            if (!$teacher->roles->contains('code', 'TEACHER')) {
                throw new OperationFailedException("Uno o varios de los profesores proporcionados no tienen el rol de profesor", 404);
            }
        }
    }

    private function prepareTeachersToSync($uidsTeachers, $teachersData, $course)
    {
        $teachersToSync = [];
        foreach ($uidsTeachers as $teacherUid) {
            $teachersToSync[$teacherUid] = [
                'uid' => generateUuid(),
                'course_uid' => $course->uid,
                'user_uid' => $teacherUid,
                'type' => $teachersData['coordinator'] && in_array($teacherUid, $teachersData['coordinator']) ? 'COORDINATOR' : 'NO_COORDINATOR'
            ];
        }

        return $teachersToSync;
    }

    private function updateCourseDb($updateData, $course)
    {
        $this->validateCourseDates($updateData, $course);

        $allowedFields = [
            'title',
            'description',
            'lms_url',
            'ects_workload',
            'realization_start_date',
            'realization_finish_date',
        ];

        // Filtra los datos del request para incluir solo los campos permitidos
        $filteredData = array_filter(
            $updateData,
            fn($key) => in_array($key, $allowedFields),
            ARRAY_FILTER_USE_KEY
        );

        foreach ($filteredData as $key => $value) {
            if ($value == null) {
                unset($filteredData[$key]);
            }
        }

        $course->fill($filteredData);
        $course->save();
    }

    private function validateCourseDates($updateData, $course)
    {
        $realizationStartDateValidate = $updateData['realization_start_date'] ?? $course->realization_start_date;
        $realizationFinishDateValidate = $updateData['realization_finish_date'] ?? $course->realization_finish_date;

        if ($realizationStartDateValidate > $realizationFinishDateValidate) {
            throw new OperationFailedException("La fecha de inicio de realización no puede ser posterior a la fecha de fin de realización", 406);
        }

        if ($course->belongs_to_educational_program) {
            $this->validationCourseBelongsToEducationalProgram($realizationStartDateValidate, $course);
        } else {
            $this->validationCourseNotBelongsToEducationalProgram($realizationFinishDateValidate, $course);
        }
    }

    private function validationCourseBelongsToEducationalProgram($realizationStartDate, $course)
    {
        $educationalProgram = $course->educational_program;

        if ($educationalProgram->validate_student_registrations || $educationalProgram->cost && $educationalProgram->cost > 0) {
            if ($realizationStartDate < $educationalProgram->enrolling_finish_date) {
                throw new OperationFailedException("La fecha de inicio del curso no puede ser anterior a la fecha de fin de matriculación del programa formativo", 406);
            }
        } else {
            if ($realizationStartDate < $educationalProgram->inscription_finish_date) {
                throw new OperationFailedException("La fecha de inicio de realización del curso no puede ser anterior a la fecha de fin de inscripción del programa formativo", 406);
            }
        }
    }

    // Validamos que la fecha de realización sea posterior a la de inscripción o matriculación
    private function validationCourseNotBelongsToEducationalProgram($realizationStartDate, $course)
    {
        if ($course->validate_student_registrations || $course->cost && $course->cost > 0) {
            if ($realizationStartDate < $course->enrolling_finish_date) {

                throw new OperationFailedException("La fecha de inicio del curso no puede ser anterior a la fecha de fin de matriculación", 406);
            }
        } else {
            if ($realizationStartDate < $course->inscription_finish_date) {
                throw new OperationFailedException("La fecha de inicio de realización del curso no puede ser anterior a la fecha de fin de inscripción", 406);
            }
        }
    }

    private function saveCourseConfirm($courseConfirm)
    {
        CoursesModel::where("uid", $courseConfirm["poa_uid"])->update([
            "course_lms_id" => $courseConfirm["course_lms_id"],
            "lms_url" => $courseConfirm["lms_url"]
        ]);
    }

    private function validateCourseConfirms($courseConfirm)
    {
        $messages = [
            "course_lms_id.required" => "El campo course_lms_id es obligatorio",
            "course_lms_id.string" => "El campo course_lms_id debe ser un string",
            "course_lms_id.unique" => "Ya existe un curso con el uid de LMS proporcionado",
            "poa_uid.required" => "El campo poa_uid es obligatorio",
            "poa_uid.string" => "El campo poa_uid debe ser un string",
            "poa_uid.exists" => "No existe un curso con el uid de POA proporcionado",
            "lms_url.required" => "El campo lms_url es obligatorio",
            "lms_url.url" => "El campo lms_url debe ser una URL válida"
        ];

        $rules = [
            "course_lms_id" => "required|string|unique:courses,course_lms_id",
            "poa_uid" => "required|string|exists:courses,uid",
            "lms_url" => "required|url"
        ];

        $validator = Validator::make($courseConfirm, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors();
        }
    }
}
