<?php

namespace App\Http\Controllers\Credentials;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use App\Models\CoursesStudentsModel;
use App\Models\EducationalProgramsModel;
use App\Models\EducationalProgramsStudentsModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\UsersModel;
use App\Services\CertidigitalService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class StudentsCredentialsController extends BaseController
{
    protected $certidigitalService;

    public function __construct(CertidigitalService $certidigitalService)
    {
        $this->certidigitalService = $certidigitalService;
    }

    public function index()
    {

        return view(
            'credentials.students.index',
            [
                "page_name" => "Credenciales de estudiantes",
                "page_title" => "Credenciales de estudiantes",
                "resources" => [
                    "resources/js/credentials_module/students_credentials.js"
                ],
                "tabulator" => true,
                "submenuselected" => "credentials-students",
            ]
        );
    }

    public function getStudents(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = UsersModel::query()->with("roles");

        $query = UsersModel::query()->whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        });

        if ($search) {
            $query->where('first_name', 'ILIKE', "%{$search}%")
                ->orWhere('last_name', 'ILIKE', "%{$search}%")
                ->orWhere('email', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function emitCredentials(Request $request)
    {
        $coursesUids = $request->get('courses');
        $userUid = $request->get('user_uid');

        // Tipos de programa asociados a los cursos
        $educationalProgramTypesCourses = EducationalProgramTypesModel::whereIn('uid', function ($query) use ($coursesUids) {
            $query->select('educational_program_type_uid')
                ->from('courses')
                ->whereIn('uid', $coursesUids);
        })->get();

        // Rol del usuario
        $userRoles = auth()->user()->roles()->get()->pluck('code')->toArray();

        // Comprobación de si el usuario tiene el rol correcto para emitir en base a los tipos de programa
        foreach ($educationalProgramTypesCourses as $educationalProgramTypeCourse) {
            if ($educationalProgramTypeCourse->managers_can_emit_credentials && in_array('MANAGEMENT', $userRoles)) {
                continue;
            } else if ($educationalProgramTypeCourse->teachers_can_emit_credentials && in_array('TEACHER', $userRoles)) {
                continue;
            }

            throw new OperationFailedException('No tienes permisos para emitir credenciales en alguno de los cursos');
        }

        $coursesStudentWithEmissions = CoursesStudentsModel::where('user_uid', $userUid)
            ->whereIn('course_uid', $coursesUids)
            ->where('emissions_block_uuid', "!=", null)
            ->exists();

        if ($coursesStudentWithEmissions) {
            throw new OperationFailedException('No se pueden emitir credenciales porque alguno de los cursos ya tiene credenciales emitidas');
        }

        $coursesWithoutCredential = CoursesModel::whereIn('uid', $coursesUids)->where("certidigital_credential_uid", null)->exists();
        if ($coursesWithoutCredential) {
            throw new OperationFailedException('No se pueden emitir credenciales porque alguno de los cursos no tiene credenciales asociadas');
        }

        foreach ($coursesUids as $courseUid) {
            $this->certidigitalService->emissionCredentialsCourse($courseUid, [$userUid]);
        }

        return response()->json(['message' => 'Credenciales generadas correctamente'], 200);
    }

    public function sendCredentials(Request $request)
    {
        $coursesUids = $request->get('courses');
        $educationalProgramsUids = $request->get('educational_programs');
        $userUid = $request->get('user_uid');

        // Comprobación si hay algún alumno sin credencial emitida
        $coursesStudentWithoutEmissions = CoursesStudentsModel::where('user_uid', $userUid)
            ->whereIn('course_uid', $coursesUids)
            ->where('emissions_block_uuid', null)
            ->exists();

        // Comprobación si hay algún alumno sin credencial emitida en los programas formativos
        $educationalProgramsStudentWithoutEmissions = EducationalProgramsStudentsModel::where('user_uid', $userUid)
            ->whereIn('educational_program_uid', $educationalProgramsUids)
            ->where('emissions_block_uuid', null)
            ->exists();

        if ($coursesStudentWithoutEmissions || $educationalProgramsStudentWithoutEmissions) {
            throw new OperationFailedException('No se pueden enviar credenciales porque alguno de los cursos o programas formativos no las tiene emitidas');
        }

        if (count($coursesUids)) {
            $this->certidigitalService->sendCourseCredentials($coursesUids, $userUid);
        }

        if (count($educationalProgramsUids)) {
            $this->certidigitalService->sendCredentialsEducationalPrograms($educationalProgramsUids, $userUid);
        }

        return response()->json(['message' => 'Credenciales enviadas correctamente'], 200);
    }

    public function sealCredentials(Request $request)
    {
        $coursesUids = $request->get('courses');
        $educationalProgramsUids = $request->get('educational_programs');
        $userUid = $request->get('user_uid');

        // Comprobación si hay algún alumno sin credencial emitida
        $coursesStudentWithoutEmissions = CoursesStudentsModel::where('user_uid', $userUid)
            ->whereIn('course_uid', $coursesUids)
            ->where('emissions_block_uuid', null)
            ->exists();

        $educationalProgramsWithoutEmissions = EducationalProgramsModel::whereHas('students', function ($query) use ($userUid) {
            $query->where('user_uid', $userUid)
                ->where('emissions_block_uuid', null);
        })->whereIn('uid', $educationalProgramsUids)->exists();

        if ($educationalProgramsWithoutEmissions || $coursesStudentWithoutEmissions) {
            throw new OperationFailedException('No se pueden sellar credenciales porque alguno de los objetos de aprendizaje no las tiene emitidas');
        }

        if (count($coursesUids)) $this->certidigitalService->sealCoursesCredentials($coursesUids, $userUid);
        if (count($educationalProgramsUids)) $this->certidigitalService->sealEducationalProgramsCredentials($educationalProgramsUids, $userUid);

        return response()->json(['message' => 'Credenciales selladas correctamente'], 200);
    }

    public function getCoursesStudents(Request $request, $student_uid)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $queries = [];
        $queries[] = $this->getQueryCoursesStudents($student_uid, $search);
        $queries[] = $this->getQueryEducationalProgramsStudents($student_uid, $search);

        $learning_objects_query = array_shift($queries);
        foreach ($queries as $query) {
            $learning_objects_query->unionAll($query);
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $learning_objects_query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $learning_objects_query->paginate($size);

        return response()->json($data, 200);
    }

    private function getQueryCoursesStudents($studentUid, $search)
    {
        $query = CoursesModel::select([
            'courses.uid',
            'courses.title',
            DB::raw("'course' as learning_object_type"),
            'courses_students.emissions_block_uuid',
            'courses_students.credential_sealed',
            'courses_students.credential_sent',
        ])
            ->leftJoin('courses_students', 'courses_students.course_uid', '=', 'courses.uid')
            ->where('courses_students.user_uid', $studentUid);

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        return $query;
    }

    private function getQueryEducationalProgramsStudents($studentUid, $search)
    {
        $query = EducationalProgramsModel::select([
            'educational_programs.uid',
            'educational_programs.name as title',
            DB::raw("'educational_program' as learning_object_type"),
            'educational_programs_students.emissions_block_uuid',
            'educational_programs_students.credential_sealed',
            'educational_programs_students.credential_sent',
        ])
            ->leftJoin('educational_programs_students', 'educational_programs_students.educational_program_uid', '=', 'educational_programs.uid')
            ->where('educational_programs_students.user_uid', $studentUid);

        if ($search) {
            $query->where('educational_programs.name', 'ILIKE', "%{$search}%");
        }

        return $query;
    }
}
