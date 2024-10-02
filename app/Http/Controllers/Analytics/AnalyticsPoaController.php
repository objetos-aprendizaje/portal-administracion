<?php

namespace App\Http\Controllers\Analytics;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\CoursesModel;
use App\Models\EducationalResourcesModel;
use Illuminate\Support\Facades\DB;

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

    public function getPoaAccesses(Request $request){
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        // Consulta para obtener el primer y último acceso de cada curso
        $query = DB::table('courses')
            ->join('courses_accesses', 'courses.uid', '=', 'courses_accesses.course_uid')
            ->select('courses.title',
                    DB::raw('MIN(courses_accesses.access_date) as first_access'),
                    DB::raw('MAX(courses_accesses.access_date) as last_access'))
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
    public function getPoaResourcesAccesses(Request $request){
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        // Consulta para obtener el primer y último acceso de cada curso
        $query = DB::table('educational_resources')
            ->join('educational_resource_access', 'educational_resources.uid', '=', 'educational_resource_access.educational_resource_uid')
            ->select('educational_resources.title',
                    DB::raw('MIN(educational_resource_access.date) as first_access'),
                    DB::raw('MAX(educational_resource_access.date) as last_access'))
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
}


