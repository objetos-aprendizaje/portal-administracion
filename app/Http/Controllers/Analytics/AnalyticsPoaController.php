<?php

namespace App\Http\Controllers\Analytics;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\CoursesModel;
use App\Models\EducationalResourcesModel;

class AnalyticsPoaController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {


        return view(
            'analytics.poa.index',
            [
                "page_name" => "Analíticas de objetos de aprendizaje y recursos",
                "page_title" => "Analíticas de objetos de aprendizaje y recursos",
                "resources" => [
                    "resources/js/analytics_module/analytics_poa.js",
                    "resources/js/analytics_module/d3.js"
                ],
                "tabulator" => true,
                "submenuselected" => "analytics-poa",
            ]
        );
    }

    public function getPoa(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CoursesModel::withCount('accesses');



        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }else{
            $query->orderBy('accesses_count', 'DESC');
        }
        // Ahora aplicamos la paginación antes de obtener los resultados.
        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getPoaGraph() {

        $query = CoursesModel::withCount('accesses')->orderBy('accesses_count', 'DESC')->get()->toArray();

        return response()->json($query, 200);

    }

    public function getPoaResources(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = EducationalResourcesModel::withCount('accesses');



        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }else{
            $query->orderBy('accesses_count', 'DESC');
        }
        // Ahora aplicamos la paginación antes de obtener los resultados.
        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    public function getPoaGraphResources() {

        $query = EducationalResourcesModel::withCount('accesses')->orderBy('accesses_count', 'DESC')->get()->toArray();

        return response()->json($query, 200);

    }
}


