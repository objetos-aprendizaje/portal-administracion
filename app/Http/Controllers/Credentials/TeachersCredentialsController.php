<?php

namespace App\Http\Controllers\Credentials;

use App\Models\UsersModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;


class TeachersCredentialsController extends BaseController
{

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
            $query->where('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getCoursesTeacher(Request $request, $teacher_uid) {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $user = UsersModel::where('uid', $teacher_uid)->first();
        $query = $user->coursesTeachers();

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }
}
