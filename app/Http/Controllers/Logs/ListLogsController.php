<?php
namespace App\Http\Controllers\Logs;

use Illuminate\Routing\Controller as BaseController;
use App\Models\LogsModel;

use Illuminate\Http\Request;

class ListLogsController extends BaseController
{

    public function index()
    {

        return view(
            'logs.list_logs/index',
            [
                "page_name" => "Listado de logs",
                "page_title" => "Listado de logs",
                "resources" => [
                    "resources/js/logs_module/list_logs.js"
                ],
                "tabulator" => true,
            ]
        );
    }

    /**
     * Obtiene todas las convocatorias.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogs(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = LogsModel::query()
            ->join('users', 'logs.user_uid', '=', 'users.uid')
            ->select('logs.*', 'users.first_name as user_first_name', 'users.last_name as user_last_name')
            ->with('user');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('type', 'LIKE', "%{$search}%")
                    ->orWhere('entity', 'LIKE', "%{$search}%")
                    ->orWhere('users.last_name', 'LIKE', "%{$search}%")
                    ->orWhere('users.first_name', 'LIKE', "%{$search}%");
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
