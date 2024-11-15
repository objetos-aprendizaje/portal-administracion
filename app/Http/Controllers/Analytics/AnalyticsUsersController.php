<?php

namespace App\Http\Controllers\Analytics;

use App\Models\CoursesStudentsModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\UserRolesModel;
use App\Models\UsersAccessesModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;


class AnalyticsUsersController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        // Obtenemos el total de usuarios
        $total_users = UsersModel::count();

        $userRoles = UserRolesModel::all();

        return view(
            'analytics.users_per_role.index',
            [
                "page_name" => "Analíticas de usuarios",
                "page_title" => "Analíticas de usuarios",
                "resources" => [
                    "resources/js/analytics_module/analytics_users.js"
                ],
                "roles_with_user_count" => "roles_with_user_count",
                "total_users" => $total_users,
                "userRoles" => $userRoles,
                "tabulator" => true,
                "submenuselected" => "analytics-users",
                "flatpickr" => true,
                "tomselect" => true,
            ]
        );
    }

    public function getUsersRoles(Request $request)
    {

        $size = $request->get('size', 1);
        $sort = $request->get('sort');

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

    public function getUsersRolesGraph()
    {

        $query = UserRolesModel::withCount('users')->get()->toArray();

        return response()->json($query, 200);
    }
    public function getStudents(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = UsersModel::query()->with('roles');

        if ($search) {
            $query->where('first_name', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $query->addSelect([
            'last_login' => UsersAccessesModel::select('date')
                ->whereColumn('user_uid', 'users.uid')
                ->orderBy('date', 'desc')
                ->limit(1)
        ]);

        $query->addSelect([
            'count_courses' => CoursesStudentsModel::selectRaw("count(courses_students.course_uid)")
                ->whereColumn('user_uid', 'users.uid')
        ]);

        if ($filters) {
            foreach ($filters as $filter) {
                if ($filter['database_field'] == 'creation_date') {
                    if (count($filter['value']) == 2) {
                        $query->whereBetween('created_at', [$filter['value'][0], $filter['value'][1]]);
                    }
                } else if ($filter['database_field'] == "roles") {
                    $query->whereHas('roles', function ($query) use ($filter) {
                        $query->whereIn('user_roles.uid', $filter['value']);
                    });
                }
            }
        }

        $data = $query->paginate($size);


        return response()->json($data, 200);
    }

    public function getStudentsData(Request $request)
    {

        $requestData = $request->all();

        $data = [];
        $first_graph = "";
        $second_graph = "";
        $third_graph = "";

        if ($requestData['filter_type'] == null) {
            $dateFormat = 'YYYY-MM-DD';
        } else {
            $dateFormat = $requestData['filter_type'];
        }

        if ($requestData['filter_date'] == null) {

            $hoy = Carbon::today();
            $lunes = $hoy->copy()->startOfWeek();
            $lunesString = $lunes->format('Y-m-d');
            $domingo = $hoy->copy()->endOfWeek();
            $domingoString = $domingo->format('Y-m-d');
            $requestData['filter_date'] = $lunesString . "," . $domingoString;
        }

        $dates = explode(",", $requestData['filter_date']);

        $first_graph = DB::table('users_accesses')
            ->select(DB::raw('to_char(date, \'' . $dateFormat . '\') as period'), DB::raw('count(*) as access_count'))
            ->where('user_uid', $requestData['user_uid'])
            ->whereBetween('date', [
                Carbon::parse($dates[0])->startOfDay(),
                Carbon::parse($dates[1])->endOfDay()
            ])
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $maxFristGraphCount = 0;
        if (!empty($first_graph->max('access_count'))) {
            $maxFristGraphCount = $first_graph->max('access_count');
        }

        $second_graph = DB::table('courses_accesses')
            ->select(DB::raw('to_char(access_date, \'' . $dateFormat . '\') as period'), DB::raw('count(*) as access_count'))
            ->where('user_uid', $requestData['user_uid'])
            ->whereBetween('access_date', [
                Carbon::parse($dates[0])->startOfDay(),
                Carbon::parse($dates[1])->endOfDay()
            ])
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $maxSecondGraphCount = 0;
        if (!empty($second_graph->max('access_count'))) {
            $maxSecondGraphCount = $second_graph->max('access_count');
        }

        $third_graph = DB::table('educational_resource_access')
            ->select(DB::raw('to_char(date, \'' . $dateFormat . '\') as period'), DB::raw('count(*) as access_count'))
            ->where('user_uid', $requestData['user_uid'])
            ->whereBetween('date', [
                Carbon::parse($dates[0])->startOfDay(),
                Carbon::parse($dates[1])->endOfDay()
            ])
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $maxThirdGraphCount = 0;
        if (!empty($third_graph->max('access_count'))) {
            $maxThirdGraphCount = $third_graph->max('access_count');
        }

        $startDate = Carbon::parse($dates[0]);
        $endDate = Carbon::parse($dates[1]);

        // Dependiendo del $groupMode ('days', 'months', 'years')
        if ($dateFormat == 'YYYY-MM-DD') {
            $period = CarbonPeriod::create($startDate, '1 day', $endDate);
            $dateFormatPeriod = 'Y-m-d'; // Formato para días
        } elseif ($dateFormat == 'YYYY-MM') {
            $period = CarbonPeriod::create($startDate, '1 month', $endDate);
            $dateFormatPeriod = 'Y-m'; // Formato para meses
        } elseif ($dateFormat == 'YYYY') {
            $period = CarbonPeriod::create($startDate, '1 year', $endDate);
            $dateFormatPeriod = 'Y'; // Formato para años
        }

        $dataFromDbFrist = $first_graph->pluck('access_count', 'period')->toArray();
        $dataFromDbSecond = $second_graph->pluck('access_count', 'period')->toArray();
        $dataFromDbThird = $third_graph->pluck('access_count', 'period')->toArray();

        $fullData1 = [];
        $fullData2 = [];
        $fullData3 = [];

        foreach ($period as $date) {

            $formattedDate = $date->format($dateFormatPeriod);

            $fullData1[] = [
                'period' => $formattedDate,
                'access_count' => $dataFromDbFrist[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];
            $fullData2[] = [
                'period' => $formattedDate,
                'access_count' => $dataFromDbSecond[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];
            $fullData3[] = [
                'period' => $formattedDate,
                'access_count' => $dataFromDbThird[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];
        }
        $data[] = $fullData1;
        $data[] = $fullData2;
        $data[] = $fullData3;
        $data[] = $requestData['filter_date'];
        $data[] = $dateFormat;
        $data[] = $maxFristGraphCount;
        $data[] = $maxSecondGraphCount;
        $data[] = $maxThirdGraphCount;

        $lastLogin = UsersAccessesModel::select('date')
            ->where('user_uid', $requestData['user_uid'])
            ->orderBy('date', 'desc')
            ->first();

        $data['last_login'] = $lastLogin->date ?? null;

        $data['count_inscribed_courses'] = CoursesStudentsModel::where('user_uid', $requestData['user_uid'])
            ->count();

        return response()->json($data, 200);
    }
}
