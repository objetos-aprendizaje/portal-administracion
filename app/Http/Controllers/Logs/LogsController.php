<?php

namespace App\Http\Controllers\Logs;

use Illuminate\Routing\Controller as BaseController;
use App\Models\LogsModel;
use App\Models\UsersModel;

use Illuminate\Http\Request;

class LogsController extends BaseController
{

    public function index()
    {
        // Obtener registros únicos de la base de datos utilizando distinct
        $logs = LogsModel::select('entity')->distinct()->get()->toArray();

        // Inicializar el array de opciones
        $entities = [];

        // Recorrer los registros y agregar al array de opciones
        foreach ($logs as $log) {
            $entities[] = [
                'uid' => $log["entity"],
                'name' => $log["entity"],
            ];
        }

        $users = UsersModel::all()->toArray();


        return view(
            'logs.list_logs/index',
            [
                "page_name" => "Listado de logs",
                "page_title" => "Listado de logs",
                "resources" => [
                    "resources/js/logs_module/list_logs.js"
                ],
                "tabulator" => true,
                "entities" => $entities,
                "users" => $users,
                "tomselect" => true,
                "flatpickr" => true,
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

        $filters = $request->get('filters');

        $query = LogsModel::query()
            ->join('users', 'logs.user_uid', '=', 'users.uid')
            ->select('logs.*', 'users.first_name as user_first_name', 'users.last_name as user_last_name')
            ->with('user');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->Where('entity', 'LIKE', "%{$search}%")
                    ->orWhere('info', 'LIKE', "%{$search}%")
                    ->orWhere('users.last_name', 'LIKE', "%{$search}%")
                    ->orWhere('users.first_name', 'LIKE', "%{$search}%");
            });
        }



        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        if ($filters) $this->applyFilters($filters, $query);

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public static function createLog($info, $entity = null, $user_uid = null)
    {
        $log = new LogsModel();
        $log->uid = generate_uuid();
        $log->info = $info;
        $log->entity = $entity;
        $log->user_uid = $user_uid;
        $log->save();
    }

    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == 'date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('logs.created_at', '<=', $filter['value'][1])
                        ->where('logs.created_at', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('created_at', '<=', $filter['value'])
                        ->whereDate('created_at', '>=', $filter['value']);
                }
            } else if ($filter['database_field'] == 'entity') {
                $query->whereIn('entity', $filter['value']);
            } else if ($filter['database_field'] == 'users') {
                $query->whereIn('logs.user_uid', $filter['value']);
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }
}
