<?php

namespace App\Http\Controllers\Analytics;

use App\Models\CategoriesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\CoursesModel;
use App\Models\EducationalResourcesModel;
use App\Models\EducationalResourceTypesModel;
use App\Models\LicenseTypesModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AnalyticsResourcesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $educational_resources_types = EducationalResourceTypesModel::all();
        $categories = CategoriesModel::with('parentCategory')->get();
        $license_types = LicenseTypesModel::get();

        return view(
            'analytics.resources.index',
            [
                "page_name" => "Analíticas de recursos",
                "page_title" => "Analíticas de recursos",
                "resources" => [
                    "resources/js/analytics_module/analytics_resources.js"
                ],
                "tabulator" => true,
                "submenuselected" => "analytics-poa",
                "flatpickr" => true,
                "educational_resources_types" => $educational_resources_types,
                "categories" => $categories,
                "license_types" => $license_types,
                "tabulator" => true,
                "tomselect" => true,
            ]
        );
    }

    public function getPoaGraph()
    {
        $query = CoursesModel::withCount('accesses')->orderBy('accesses_count', 'DESC')->get()->toArray();
        return response()->json($query, 200);
    }

    public function getPoaAccesses(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        // Consulta para obtener el primer y último acceso de cada curso
        $query = DB::table('courses')
            ->join('courses_accesses', 'courses.uid', '=', 'courses_accesses.course_uid')
            ->select(
                'courses.title',
                DB::raw('MIN(courses_accesses.access_date) as first_access'),
                DB::raw('MAX(courses_accesses.access_date) as last_access')
            )
            ->groupBy('courses.uid');

        // Ordenamiento basado en los criterios del cliente
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        } else {
            // Si no se especifica ordenamiento, por defecto se ordena por el primer acceso descendente
            $query->orderBy('first_access', 'DESC');
        }

        // Paginar los resultados
        $data = $query->paginate($size);

        // Retornar la respuesta en formato JSON
        return response()->json($data, 200);
    }

    public function getPoaResources(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = EducationalResourcesModel::withCount(['accesses', 'visits' => function ($query) {
            $query->whereNull('user_uid');
        }]);

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        } else {
            $query->orderBy('accesses_count', 'DESC');
        }

        if($filters) $this->applyFilters($filters, $query);

        // Ahora aplicamos la paginación antes de obtener los resultados.
        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == "categories") {
                $query->whereHas('categories', function ($query) use ($filter) {
                    $query->whereIn('categories.uid', $filter['value']);
                });
            } else if ($filter['database_field'] == "embeddings") {
                if ($filter['value'] == 1) $query->whereNotNull('embeddings');
                else $query->whereNull('embeddings');
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }

    public function getPoaGraphResources()
    {
        $filters = request()->get('filters');

        $query = EducationalResourcesModel::withCount('accesses')->orderBy('accesses_count', 'DESC');

        if($filters) $this->applyFilters($filters, $query);

        $data = $query->get();

        if(empty($data->toArray())){
            $query = EducationalResourcesModel::withCount('accesses')->orderBy('accesses_count', 'DESC');
            $data = $query->get();
            foreach ($data as $item) {
                $item->accesses_count = 0;
            }
        }

        return response()->json($data, 200);
    }

    public function getPoaResourcesAccesses(Request $request)
    {
        $size = $request->get('size', 1);
        $sort = $request->get('sort');

        // Consulta para obtener el primer y último acceso de cada curso
        $query = DB::table('educational_resources')
            ->join('educational_resource_access', 'educational_resources.uid', '=', 'educational_resource_access.educational_resource_uid')
            ->select(
                'educational_resources.title',
                DB::raw('MIN(educational_resource_access.date) as first_access'),
                DB::raw('MAX(educational_resource_access.date) as last_access')
            )
            ->groupBy('educational_resources.uid');

        // Ordenamiento basado en los criterios del cliente
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        } else {
            // Si no se especifica ordenamiento, por defecto se ordena por el primer acceso descendente
            $query->orderBy('first_access', 'DESC');
        }

        // Paginar los resultados
        $data = $query->paginate($size);

        // Retornar la respuesta en formato JSON
        return response()->json($data, 200);
    }
    public function getCoursesData(Request $request)
    {

        $requestData = $request->all();


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

        // Obtener los accesos agrupados por mes y contar los accesos

        $accesses = DB::table('courses_accesses')
            ->select(DB::raw('to_char(access_date, \'' . $dateFormat . '\') as access_date_group'), DB::raw('count(*) as access_count'))
            ->where('course_uid', $requestData['course_uid'])
            ->whereBetween('access_date', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()])
            ->groupBy('access_date_group')
            ->orderBy('access_date_group', 'asc')
            ->get();


        $maxAccessCount = 0;
        if (!empty($accesses->max('access_count'))) {
            $maxAccessCount = $accesses->max('access_count');
        }

        $visits = DB::table('courses_visits')
            ->select(DB::raw('to_char(access_date, \'' . $dateFormat . '\') as access_date_group'), DB::raw('count(*) as access_count'))
            ->where('course_uid', $requestData['course_uid'])
            ->whereBetween('access_date', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()])
            ->groupBy('access_date_group')
            ->orderBy('access_date_group', 'asc')
            ->get();

        $maxVisitsCount = 0;
        if (!empty($visits->max('access_count'))) {
            $maxVisitsCount = $visits->max('access_count');
        }

        $differentUsers = DB::table('courses_accesses')
            ->select(DB::raw('count(DISTINCT user_uid) as different_users'))
            ->where('course_uid', $requestData['course_uid'])
            ->when(isset($dates[0]) && isset($dates[1]), function ($query) use ($dates) {
                return $query->whereBetween(DB::raw('CAST(access_date AS DATE)'), [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
            })
            ->first()->different_users;

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

        $dataFromDbAccesses = $accesses->pluck('access_count', 'access_date_group')->toArray();
        $dataFromDbVisits = $visits->pluck('access_count', 'access_date_group')->toArray();

        $fullDataAccesses = [];
        $fullDataVisits = [];

        foreach ($period as $date) {

            $formattedDate = $date->format($dateFormatPeriod);

            $fullDataAccesses[] = [
                'access_date_group' => $formattedDate,
                'access_count' => $dataFromDbAccesses[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];

            $fullDataVisits[] = [
                'access_date_group' => $formattedDate,
                'access_count' => $dataFromDbVisits[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];
        }

        $dataAccesses[] = $fullDataAccesses;
        $dataVisits[] = $fullDataVisits;

        $lastAccess = DB::table('courses_accesses as ca')
            ->join('users as u', 'ca.user_uid', '=', 'u.uid')
            ->select('ca.access_date', 'u.first_name', 'u.last_name')
            ->where('ca.course_uid', $requestData['course_uid'])
            ->orderBy('ca.access_date', 'desc')
            ->first();

        if (!empty($lastAccess)) {
            $resultLastAccess = [
                'access_date' => $lastAccess->access_date,
                'user_name' => $lastAccess->first_name . ' ' . $lastAccess->last_name,
            ];
        } else {
            $resultLastAccess = [
                'access_date' => "",
                'user_name' => "",
            ];
        }

        $lastVisit = DB::table('courses_visits as ca')
            ->join('users as u', 'ca.user_uid', '=', 'u.uid')
            ->select('ca.access_date', 'u.first_name', 'u.last_name')
            ->where('ca.course_uid', $requestData['course_uid'])
            ->orderBy('ca.access_date', 'desc')
            ->first();

        if (!empty($lastVisit)) {
            $resultLastVisit = [
                'access_date' => $lastVisit->access_date,
                'user_name' => $lastVisit->first_name . ' ' . $lastVisit->last_name,
            ];
        } else {
            $resultLastVisit = [
                'access_date' => "",
                'user_name' => "",
            ];
        }

        $enrolledCount = DB::table('courses_students')
            ->where('course_uid', $requestData['course_uid'])
            ->count();

        // Extraer solo los datos necesarios
        $dataToSend = [
            'accesses' => $dataAccesses,
            'visits' => $dataVisits,
            'last_access' => $resultLastAccess,
            'last_visit' => $resultLastVisit,
            'different_users' => $differentUsers,
            'inscribed_users' => $enrolledCount,
            'filter_date' => $requestData['filter_date'],
            'date_format' => $dateFormat,
            'max_value' => max($maxAccessCount, $maxVisitsCount)
        ];

        return response()->json($dataToSend, 200);
    }

    public function getResourcesData(Request $request)
    {

        $requestData = $request->all();


        if ($requestData['filter_type_resource'] == null) {
            $dateFormat = 'YYYY-MM-DD';
        } else {
            $dateFormat = $requestData['filter_type_resource'];
        }

        if ($requestData['filter_date_resource'] == null) {
            $hoy = Carbon::today();
            $lunes = $hoy->copy()->startOfWeek();
            $lunesString = $lunes->format('Y-m-d');
            $domingo = $hoy->copy()->endOfWeek();
            $domingoString = $domingo->format('Y-m-d');
            $requestData['filter_date_resource'] = $lunesString . "," . $domingoString;
        }

        $dates = explode(",", $requestData['filter_date_resource']);

        // Obtener los accesos agrupados por mes y contar los accesos

        $accesses = DB::table('educational_resource_access')
            ->select(DB::raw('to_char(date, \'' . $dateFormat . '\') as access_date_group'), DB::raw('count(*) as access_count'))
            ->where('educational_resource_uid', $requestData['educational_resource_uid'])
            ->whereBetween('date', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()])
            ->groupBy('access_date_group')
            ->orderBy('access_date_group', 'asc')
            ->get();

        $maxAccessCount = 0;
        if (!empty($accesses->max('access_count'))) {
            $maxAccessCount = $accesses->max('access_count');
        }

        $visits = DB::table('educational_resource_access')
            ->select(DB::raw('to_char(date, \'' . $dateFormat . '\') as access_date_group'), DB::raw('count(*) as access_count'))
            ->where('educational_resource_uid', $requestData['educational_resource_uid'])
            ->whereBetween('date', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()])
            ->whereNull('user_uid')
            ->groupBy('access_date_group')
            ->orderBy('access_date_group', 'asc')
            ->get();

        $maxVisitsCount = 0;
        if (!empty($visits->max('access_count'))) {
            $maxVisitsCount = $visits->max('access_count');
        }

        $differentUsers = DB::table('educational_resource_access')
            ->select(DB::raw('count(uid) as different_users'))
            ->where('educational_resource_uid', $requestData['educational_resource_uid'])
            ->when(isset($dates[0]) && isset($dates[1]), function ($query) use ($dates) {
                return $query->whereBetween(DB::raw('CAST(date AS DATE)'), [date('Y-m-d', strtotime($dates[0])), date('Y-m-d', strtotime($dates[1]))]);
            })
            ->first()->different_users;

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

        $dataFromDbAccesses = $accesses->pluck('access_count', 'access_date_group')->toArray();
        $dataFromDbVisits = $visits->pluck('access_count', 'access_date_group')->toArray();



        $fullDataAccesses = [];
        $fullDataVisits = [];

        foreach ($period as $date) {

            $formattedDate = $date->format($dateFormatPeriod);

            $fullDataAccesses[] = [
                'access_date_group' => $formattedDate,
                'access_count' => $dataFromDbAccesses[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];

            $fullDataVisits[] = [
                'access_date_group' => $formattedDate,
                'access_count' => $dataFromDbVisits[$formattedDate] ?? 0 // Asignar 0 si no hay datos
            ];
        }

        $dataAccesses[] = $fullDataAccesses;
        $dataVisits[] = $fullDataVisits;

        $lastAccess = DB::table('educational_resource_access as ca')
            ->join('users as u', 'ca.user_uid', '=', 'u.uid')
            ->select('ca.date', 'u.first_name', 'u.last_name')
            ->where('ca.educational_resource_uid', $requestData['educational_resource_uid'])
            ->orderBy('ca.date', 'desc')
            ->first();

        if (!empty($lastAccess)) {
            $resultLastAccess = [
                'access_date' => $lastAccess->date,
                'user_name' => $lastAccess->first_name . ' ' . $lastAccess->last_name,
            ];
        } else {
            $resultLastAccess = [
                'access_date' => "",
                'user_name' => "",
            ];
        }

        $lastVisit = DB::table('educational_resource_access as ca')
            ->select('ca.date')
            ->where('ca.educational_resource_uid', $requestData['educational_resource_uid'])
            ->whereNull('user_uid')
            ->orderBy('ca.date', 'desc')
            ->first();

        if (!empty($lastVisit)) {
            $resultLastVisit = [
                'access_date' => $lastVisit->date
            ];
        } else {
            $resultLastVisit = [
                'access_date' => ""
            ];
        }

        // Extraer solo los datos necesarios
        $dataToSend = [
            'accesses' => $dataAccesses,
            'visits' => $dataVisits,
            'last_access' => $resultLastAccess,
            'last_visit' => $resultLastVisit,
            'different_users' => $differentUsers,
            'filter_date' => $requestData['filter_date_resource'],
            'date_format' => $dateFormat,
            'max_value' => max($maxAccessCount, $maxVisitsCount)
        ];

        return response()->json($dataToSend, 200);
    }
}
