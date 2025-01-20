<?php

namespace App\Http\Controllers\Notifications;

use App\Models\GeneralNotificationsModel;
use App\Models\NotificationsPerUsersModel;
use App\Models\UsersModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Logs\LogsController;
use Illuminate\Support\Facades\DB;

class NotificationsPerUsersController extends BaseController
{
    public function index()
    {

        $notificationsPerUsers = NotificationsPerUsersModel::get()->toArray();
        return view(
            'notifications.notifications_per_users.index',
            [
                "page_name" => "Notificaciones por usuarios",
                "page_title" => "Notificaciones por usuarios",
                "resources" => [
                    "resources/js/notifications_module/notifications_per_users.js"
                ],
                "notifications_per_users" => $notificationsPerUsers,
                "tabulator" => true,
                "submenuselected" => "notifications-per-users",
            ]
        );
    }
    public function getNotificationsPerUsers(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');


        $query = UsersModel::query();

        if ($search) {
            $query->where('first_name', 'ILIKE', "%{$search}%")->orWhere('last_name', 'ILIKE', "%{$search}%");
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
    public function getNotificationsPerUser($userUid, Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = UsersModel::where('users.uid', $userUid)
            ->with(['notifications']);

        $query = $query->first();
        $query = $query->notifications();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', '%' . $search . '%')
                    ->orWhere('description', 'ILIKE', '%' . $search . '%');
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                if($order['field'] == 'pivot.view_date') {
                    $query->orderBy('user_general_notifications.view_date', $order['dir']);
                }
                else {
                    $query->orderBy($order['field'], $order['dir']);
                }
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }
}
