<?php

namespace App\Http\Controllers\Analytics;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\UserRolesModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;



class AnalyticsUsersController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        // Obtenemos el total de usuarios
        $total_users = UsersModel::count();

        return view(
            'analytics.index',
            [
                "page_name" => "Analíticas de usuarios",
                "page_title" => "Analíticas de usuarios",
                "resources" => [
                    "resources/js/analytics_module/analytics_users.js"
                ],
                "roles_with_user_count" => "roles_with_user_count",
                "total_users" => $total_users,
                "tabulator" => true,
            ]
        );
    }

    public function getUsersRoles(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        //$data = UserRolesModel::withCount('users')->get()->toArray();

        $query = UserRolesModel::withCount('users');
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }
        // Ahora aplicamos la paginación antes de obtener los resultados.
        $data = $query->paginate($size);

        $processData = [];

        foreach ($data as $usr) {
            $processData[] = (object) [
                "uid" => $usr->coursesUsers,
                "userUid" => $usr->uid,
                "name" => $usr->first_name . ' ' . $usr->last_name
            ];
        }

        return response()->json($data, 200);
    }
}
