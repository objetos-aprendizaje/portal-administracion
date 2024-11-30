<?php

namespace App\Http\Controllers\Credentials;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use App\Models\CoursesTeachersModel;
use App\Models\UsersModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Services\CertidigitalService;


class TeachersCredentialsController extends BaseController
{
    protected $certidigitalService;

    public function __construct(CertidigitalService $certidigitalService)
    {
        $this->certidigitalService = $certidigitalService;
    }

    public function index()
    {

        return view(
            'credentials.teachers.index',
            [
                "page_name" => "Credenciales de profesores",
                "page_title" => "Credenciales de profesores",
                "resources" => [
                    "resources/js/credentials_module/teachers_credentials.js"
                ],
                "tabulator" => true,
                "submenuselected" => "credentials-teachers",
            ]
        );
    }

    public function getTeachers(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = UsersModel::query()->with("roles");

        $query = UsersModel::query()->whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
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

    public function getCoursesTeacher(Request $request, $teacher_uid)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $user = UsersModel::where('uid', $teacher_uid)->first();
        $query = $user->coursesTeachers();

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%");
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

        $coursesTeachersWithEmissions = CoursesTeachersModel::where('user_uid', $userUid)
            ->whereIn('course_uid', $coursesUids)
            ->where('emissions_block_uuid', "!=", null)
            ->exists();

        if ($coursesTeachersWithEmissions) {
            throw new OperationFailedException("El profesor ya tiene credenciales emitidas para alguno de los cursos seleccionados");
        }

        $coursesWithoutCredential = CoursesModel::whereIn('uid', $coursesUids)->where("certidigital_teacher_credential_uid", null)->exists();
        if ($coursesWithoutCredential) {
            throw new OperationFailedException('No se pueden emitir credenciales porque alguno de los cursos no tiene credenciales asociadas');
        }

        foreach ($coursesUids as $courseUid) {
            $this->certidigitalService->emissionCredentialsTeacherCourse($courseUid, [$userUid]);
        }

        return response()->json(['message' => 'Credenciales emitidas correctamente'], 200);
    }

    public function sealCredentials(Request $request)
    {
        $coursesUids = $request->get('courses');
        $userUid = $request->get('user_uid');

        $courseTeachersWithEmissions = CoursesTeachersModel::where('user_uid', $userUid)
            ->whereIn('course_uid', $coursesUids)
            ->where('emissions_block_uuid', "!=", null)
            ->exists();

        if (!$courseTeachersWithEmissions) {
            throw new OperationFailedException("El profesor no tiene credenciales emitidas para alguno de los cursos seleccionados");
        }

        foreach ($coursesUids as $courseUid) {
            $this->certidigitalService->sealCourseCredentialsTeachers($courseUid, [$userUid]);
        }

        return response()->json(['message' => 'Credenciales selladas correctamente'], 200);
    }

    public function sendCredentials(Request $request)
    {
        $coursesUids = $request->get('courses');
        $userUid = $request->get('user_uid');

        $courseTeachersWithEmissions = CoursesTeachersModel::where('user_uid', $userUid)
            ->whereIn('course_uid', $coursesUids)
            ->where('emissions_block_uuid', "!=", null)
            ->exists();

        if (!$courseTeachersWithEmissions) {
            throw new OperationFailedException("El profesor no tiene credenciales emitidas para alguno de los cursos seleccionados");
        }

        foreach ($coursesUids as $courseUid) {
            $this->certidigitalService->sendCourseCredentialsTeachers($courseUid, [$userUid]);
        }

        return response()->json(['message' => 'Credenciales enviadas correctamente'], 200);
    }
}
