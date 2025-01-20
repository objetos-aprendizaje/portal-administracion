<?php

namespace App\Http\Controllers\Analytics;

use App\Models\CallsModel;
use App\Models\CategoriesModel;
use App\Models\CentersModel;
use App\Models\CoursesModel;
use App\Models\CourseStatusesModel;
use App\Models\CourseTypesModel;
use App\Models\EducationalProgramTypesModel;
use App\Models\UsersModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class AnalyticsCoursesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        $coursesStatuses = CourseStatusesModel::all();
        $calls = CallsModel::all();
        $coursesTypes = CourseTypesModel::all();
        $categories = CategoriesModel::with('parentCategory')->get();
        $teachers = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'TEACHER');
        })->with('roles')->get();

        $students = UsersModel::whereHas('roles', function ($query) {
            $query->where('code', 'STUDENT');
        })->with('roles')->get();

        $centers = CentersModel::all();

        return view(
            'analytics.courses.index',
            [
                "page_name" => "Analíticas de cursos",
                "page_title" => "Analíticas de cursos",
                "resources" => [
                    "resources/js/analytics_module/analytics_courses.js"
                ],
                "tabulator" => true,
                "submenuselected" => "analytics-courses",
                "flatpickr" => true,
                "courses_statuses" => $coursesStatuses,
                "calls" => $calls,
                "courses_types" => $coursesTypes,
                "categories" => $categories,
                "teachers" => $teachers,
                "students" => $students,
                "centers" => $centers,
                "tomselect" => true,
                "flatpickr" => true,
            ]
        );
    }

    public function getCoursesStatusesGraph()
    {
        return CourseStatusesModel::select("uid", "name", "code")
            ->selectSub(function ($query) {
                $query->selectRaw('COUNT(courses.uid)')
                    ->from('courses')
                    ->whereColumn('course_status_uid', 'course_statuses.uid');
            }, 'courses_count')->get();
    }

    public function getPoaGraph(Request $request)
    {
        $filters = $request->filters;

        $query = CoursesModel::select("courses.uid", "courses.title")
            ->selectSub(function ($query) {
                $query->selectRaw('COUNT(courses_accesses.uid)')
                    ->from('courses_accesses')
                    ->whereColumn('courses.uid', 'courses_accesses.course_uid');
            }, 'count')
            ->orderBy('count', 'DESC');

        if ($filters) {
            $this->applyFilters($filters, $query);
        }

        $data = $query->get();

        if (empty($data->toArray())) {
            $query = CoursesModel::select("courses.uid", "courses.title");
            $data = $query->get();
            foreach ($data as $item) {
                $item->count = 0;
            }
        }

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
            ->select(DB::raw('count(user_uid) as different_users'))
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

    public function getCourses(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = CoursesModel::withCount('visits')->withCount('accesses');

        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        } else {
            $query->orderBy('visits_count', 'DESC');
        }

        if ($filters) {
            $this->applyFilters($filters, $query);
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    private function applyFilters($filters, &$query)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == "center") {
                $query->where("center", 'ILIKE', "%{$filter['value']}%");
            } elseif ($filter['database_field'] == 'inscription_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('inscription_start_date', '<=', $filter['value'][1])
                        ->where('inscription_finish_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('inscription_start_date', '<=', $filter['value'])
                        ->whereDate('inscription_finish_date', '>=', $filter['value']);
                }
            } elseif ($filter['database_field'] == 'realization_date') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('realization_start_date', '<=', $filter['value'][1])
                        ->where('realization_finish_date', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('realization_start_date', '<=', $filter['value'])
                        ->whereDate('realization_finish_date', '>=', $filter['value']);
                }
            } elseif ($filter['database_field'] == "coordinators_teachers") {
                $teachersUids = $filter['value'];
                $query->whereHas('teachers', function ($query) use ($teachersUids) {
                    $query->whereIn('users.uid', $teachersUids)
                        ->where('type', 'COORDINATOR');
                });
            } elseif ($filter['database_field'] == "no_coordinators_teachers") {
                $teachersUids = $filter['value'];
                $query->whereHas('teachers', function ($query) use ($teachersUids) {
                    $query->whereIn('users.uid', $teachersUids)
                        ->where('type', 'NO_COORDINATOR');
                });
            } elseif ($filter['database_field'] == 'creator_user_uid') {
                $query->whereIn('creator_user_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'categories') {
                $categoriesUids = $filter['value'];
                $query->whereHas('categories', function ($query) use ($categoriesUids) {
                    $query->whereIn('categories.uid', $categoriesUids);
                });
            } elseif ($filter['database_field'] == 'course_statuses') {
                $query->whereIn('course_status_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'calls') {
                $query->whereIn('call_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'course_types') {
                $query->whereIn('course_type_uid', $filter['value']);
            } elseif ($filter['database_field'] == 'min_ects_workload') {
                $query->where('ects_workload', '>=', $filter['value']);
            } elseif ($filter['database_field'] == 'max_ects_workload') {
                $query->where('ects_workload', '<=', $filter['value']);
            } elseif ($filter['database_field'] == 'min_cost') {
                $query->where('cost', '>=', $filter['value']);
            } elseif ($filter['database_field'] == 'max_cost') {
                $query->where('cost', '<=', $filter['value']);
            } elseif ($filter['database_field'] == 'min_required_students') {
                $query->where('min_required_students', '>=', $filter['value']);
            } elseif ($filter['database_field'] == 'max_required_students') {
                $query->where('min_required_students', '<=', $filter['value']);
            } elseif ($filter['database_field'] == 'learning_results') {
                $query->with([
                    'blocks.learningResults'
                ])->whereHas('blocks.learningResults', function ($query) use ($filter) {
                    $query->whereIn('learning_results.uid', $filter['value']);
                });
            } elseif ($filter['database_field'] == "embeddings") {
                if ($filter['value'] == 1) {
                    $query->whereNotNull('embeddings');
                } else {
                    $query->whereNull('embeddings');
                }
            } else {
                $query->where($filter['database_field'], $filter['value']);
            }
        }
    }
}
