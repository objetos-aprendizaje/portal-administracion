<?php

namespace App\Http\Controllers\Analytics;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\CoursesModel;
use Illuminate\Support\Facades\DB;
use App\Models\CoursesStudentsModel;
use App\Models\CategoriesModel;
use Carbon\Carbon;
use DateTime;


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
            ]
        );

    }

    public function getTopCategories(Request $request){

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = DB::table('categories')
            ->select('categories.name', DB::raw('COUNT(courses_students.user_uid) as student_count'))
            ->leftJoin('course_categories', 'categories.uid', '=', 'course_categories.category_uid')
            ->leftJoin('courses', 'course_categories.course_uid', '=', 'courses.uid')
            ->leftJoin('courses_students', 'courses.uid', '=', 'courses_students.course_uid') // Corrección aquí
            ->groupBy('categories.uid')
            ->orderByDesc('student_count');


        // Aplicar búsqueda por nombre de categoría si es necesario
        if (!empty($search)) {
            $query->where('categories.name', 'LIKE', "%{$search}%");
        }

        // Aplicar paginación
        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getTopCategoriesGraph(){



        $query = DB::table('categories')
            ->select('categories.name', DB::raw('COUNT(courses_students.user_uid) as student_count'))
            ->leftJoin('course_categories', 'categories.uid', '=', 'course_categories.category_uid')
            ->leftJoin('courses', 'course_categories.course_uid', '=', 'courses.uid')
            ->leftJoin('courses_students', 'courses.uid', '=', 'courses_students.course_uid') // Corrección aquí
            ->groupBy('categories.uid')
            ->orderByDesc('student_count')->get()->toArray();



        return response()->json($query, 200);
    }

}


