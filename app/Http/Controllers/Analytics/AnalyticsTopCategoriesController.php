<?php

namespace App\Http\Controllers\Analytics;

use App\Models\CategoriesModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AnalyticsTopCategoriesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return view(
            'analytics.top_categories.index',
            [
                "page_name" => "TOP Categorias",
                "page_title" => "TOP Categorias",
                "resources" => [
                    "resources/js/analytics_module/analytics_top_categories.js"
                ],
                "tabulator" => true,
                "submenuselected" => "analytics-top-categories",
                "tomselect" => true,
                "flatpickr" => true
            ]
        );
    }

    public function getTopCategories(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = $this->getTopCategoriesQuery($filters);

        // Aplicar búsqueda por nombre de categoría si es necesario
        if (!empty($search)) {
            $query->where('categories.name', 'LIKE', "%{$search}%");
        }

        // Aplicar ordenamiento
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        } else {
            $query->orderBy('student_count', 'desc');
        }

        // Aplicar paginación
        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getTopCategoriesGraph()
    {
        $filters = request()->get('filters');
        $query = $this->getTopCategoriesQuery($filters);
        $query->orderByDesc('student_count');
        $data = $query->get();

        return response()->json($data, 200);
    }

    private function getTopCategoriesQuery($filters = null)
    {
        return CategoriesModel::select('categories.name')
            ->selectSub(function ($query) use ($filters) {
                $query->selectRaw('COUNT(courses_students.user_uid)')
                    ->from('courses_students as courses_students')
                    ->join('courses', 'courses.uid', '=', 'courses_students.course_uid')
                    ->join('course_categories', 'courses.uid', '=', 'course_categories.course_uid')
                    ->whereColumn('categories.uid', 'course_categories.category_uid');

                if ($filters) {
                    $this->applyFilters($query, $filters);
                }
            }, 'student_count');
    }

    private function applyFilters(&$query, $filters)
    {
        foreach ($filters as $filter) {
            if ($filter['database_field'] == 'acceptance_status') {
                $query->whereIn('courses_students.acceptance_status', $filter['value']);
            } elseif ($filter['database_field'] == "status") {
                $query->whereIn('courses_students.status', $filter['value']);
            } elseif ($filter['database_field'] == 'created_at') {
                if (count($filter['value']) == 2) {
                    // Si recibimos un rango de fechas
                    $query->where('courses_students.created_at', '<=', $filter['value'][1])
                        ->where('courses_students.created_at', '>=', $filter['value'][0]);
                } else {
                    // Si recibimos solo una fecha
                    $query->whereDate('courses_students.created_at', '<=', $filter['value'])
                        ->whereDate('courses_students.created_at', '>=', $filter['value']);
                }
            }
        }
    }
}
