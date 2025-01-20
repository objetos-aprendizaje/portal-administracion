<?php

namespace App\Http\Controllers\Analytics;

use App\Exceptions\OperationFailedException;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\CoursesModel;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\DB;
use DateTime;


class AnalyticsAbandonedController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {


        return view(
            'analytics.abandoned.index',
            [
                "page_name" => "Abandonos de cursos",
                "page_title" => "Abandonos de cursos",
                "resources" => [
                    "resources/js/analytics_module/analytics_abandoned.js"
                ],
                "tabulator" => true,
                "submenuselected" => "analytics-abandoned",
            ]
        );
    }

    public function saveThresholdAbandonedCourses(Request $request)
    {
        $thresholdAbandonedCourses = $request->threshold_abandoned_courses;

        if (!is_numeric($thresholdAbandonedCourses) || $thresholdAbandonedCourses < 0) {
            throw new OperationFailedException("El número introducido no es válido");
        }

        GeneralOptionsModel::where('option_name', 'threshold_abandoned_courses')->update(['option_value' => $thresholdAbandonedCourses]);

        return response()->json(['message' => 'Umbral actualizado correctamente'], 200);
    }

    public function getAbandonedGraph()
    {

        $thresholdAbandonedCourses = app('general_options')['threshold_abandoned_courses'];

        $query = CoursesModel::select(
            'courses.uid',
            'courses.title',
            DB::raw("realization_start_date + interval '" . $thresholdAbandonedCourses . " days' as abandoned_date")
        )
            ->with(['accesses', 'students' => function ($query) {
                $query->where('status', 'ENROLLED')
                    ->where('acceptance_status', 'ACCEPTED');
            }])
            ->withCount(['students as enrolled_accepted_students_count' => function ($query) {
                $query->where('status', 'ENROLLED')
                    ->where('acceptance_status', 'ACCEPTED');
            }])
            ->whereHas('status', function ($query) {
                $query->where('code', 'DEVELOPMENT');
            })
            ->whereHas('accesses')
            ->whereNotNull('lms_url')->get()->toArray();

        $newData = [];
        $fechaHoy = new DateTime();
        foreach ($query as $index => $course) {
            $fecha = new DateTime($course['abandoned_date']);
            if ($fechaHoy <= $fecha) {
                //no hay abandonos
                $course['abandoned'] = 0;
            } else {

                //verificamos accesos

                if (!isset($course['accesses'])) {
                    //Si no hay ningun acceso es que todos los alumnos han abandonado
                    $course['abandoned'] = $course['enrolled_accepted_students_count'];
                } else {
                    //existen accesos, verificamos que usuarios han accedido y cuales no para crear un nuevo array de datos
                    $alumnos = $course['students'];
                    $accesos = $course['accesses'];

                    $data = $this->verificarAccesosAlumnos($alumnos, $accesos);

                    if (!empty($data)) {
                        $course['abandoned'] = count($data);
                        $course['abandoned_users'] = $data;
                    } else {
                        $course['abandoned'] = 0;
                        $course['abandoned_users'] = "";
                    }
                }
            }
            $newData[$index] = $course;
        }

        return response()->json($newData, 200);
    }

    private function verificarAccesosAlumnos($alumnos, $accesos)
    {

        $fechaHoy = new DateTime();
        $abandonedUsers = [];

        //revisamos todos los accesos por usuario y cogemos el último acceso para verificar el abandono.
        foreach ($alumnos as $alumno) {

            // user_uid que buscas
            $userUidBuscado = $alumno['uid'];

            // Filtrar las coincidencias por el user_uid
            $coincidencias = array_filter($accesos, function ($access) use ($userUidBuscado) {
                return $access['user_uid'] === $userUidBuscado;
            });

            // Si hay coincidencias, obtener la fecha mayor
            if (!empty($coincidencias)) {
                $resultado = array_reduce($coincidencias, function ($carry, $item) {
                    if (!$carry || $item['access_date'] > $carry['access_date']) {
                        return $item;
                    }
                    return $carry;
                });

                // Mostrar el resultado con la fecha más reciente
                $fechaAcceso = new DateTime($resultado['access_date']);

                //calculamos la diferencia entre hoy y el último acceso
                $diferencia = $fechaHoy->diff($fechaAcceso);

                // Obtener la diferencia en días
                $dias = $diferencia->days;

                $thresholdAbandonedCourses = (int) app('general_options')['threshold_abandoned_courses'];
                if ($dias > $thresholdAbandonedCourses) {
                    //es usuario abandonado
                    $abandonedUsers[] = $alumno;
                }
                return $abandonedUsers;
            }
        }
    }
}
