<?php

namespace App\Http\Controllers\LearningObjects;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Models\NotificationsPerUsersModel;
use App\Models\UsersModel;


class EducationalResourcesPerUsersController extends BaseController
{
    public function index()
    {

        $educational_resources_per_users = NotificationsPerUsersModel::get()->toArray();

        return view(
            'learning_objects.educational_resources_per_users.index',
            [
                "page_name" => "Recursos educativos por usuarios",
                "page_title" => "Recursos educativos por usuarios",
                "resources" => [
                    "resources/js/learning_objects_module/educational_resources_per_users.js"
                ],
                "tabulator" => true,
                "tomselect" => true,
                "notifications_per_users" => $educational_resources_per_users,
                "submenuselected" => "learning-objects-educational-resources-per-users",
            ]
        );
    }

    public function getEducationalResourcesPerUsers(Request $request){

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');


        $query = UsersModel::query();

        if ($search) {
            $query->where('first_name', 'LIKE', "%{$search}%")->orWhere('last_name', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        if (!$data) {
            return response()->json(['message' => 'No hay usuarios en base de datos'], 406);
        }

        return response()->json($data, 200);

    }

    public function getEducationalResourcesPerUser($user_uid, Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = UsersModel::where('users.uid', $user_uid)
            ->with(['educationalResources']);

        $query = $query->first();
        $query = $query->educationalResources();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
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
