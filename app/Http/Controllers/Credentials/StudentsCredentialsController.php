<?php

namespace App\Http\Controllers\Credentials;

use App\Models\UsersModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;


class StudentsCredentialsController extends BaseController
{

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

    public function getCoursesStudents(Request $request, $student_uid) {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $user = UsersModel::where('uid', $student_uid)->first();
        $query = $user->coursesStudents();

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                if ($order['field'] == 'pivot.credential') {
                    $order['field'] = 'pivot_credential';
                }
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }
}
