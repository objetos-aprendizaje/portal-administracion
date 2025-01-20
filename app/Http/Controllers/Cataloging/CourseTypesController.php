<?php

namespace App\Http\Controllers\Cataloging;

use App\Exceptions\OperationFailedException;
use App\Models\CoursesModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CourseTypesModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CourseTypesController extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$this->checkAccessManagers()) {
                abort(403);
            }
            return $next($request);
        })->except('index');
    }

    public function index()
    {
        if (!$this->checkAccessManagers()) {
            return view('access_not_allowed', [
                'title' => 'No tienes permiso para administrar los tipos de cursos',
                'description' => 'El administrador ha bloqueado la administración de tipos de cursos a los gestores.'
            ]);
        }

        $courseTypes = CourseTypesModel::get()->toArray();

        return view(
            'cataloging.course_types.index',
            [
                "page_name" => "Tipos de curso",
                "page_title" => "Tipos de curso",
                "resources" => [
                    "resources/js/cataloging_module/course_types.js"
                ],
                "course_types" => $courseTypes,
                "tabulator" => true,
                "submenuselected" => "cataloging-course-types",
            ]
        );
    }

    /**
     * Obtiene todas los tipo de cursos.
     */
    public function getCourseTypes(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CourseTypesModel::query();

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    /**
     * Obtiene un tipo de curso por uid
     */
    public function getCourseType($courseTypeUid)
    {

        $courseType = CourseTypesModel::where('uid', $courseTypeUid)->first();

        if (!$courseType) {
            return response()->json(['message' => 'El tipo de curso no existe'], 406);
        }

        return response()->json($courseType, 200);
    }

    /**
     * Guarda una tipo de curso. Si recibe un uid, actualiza el tipo de curso con ese uid.
     */
    public function saveCourseType(Request $request)
    {

        $messages = [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre no puede tener menos de 3 caracteres.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'El nombre del tipo ya está en uso.',
            'course_type_uid.exists' => 'El tipo de curso no exite.',
        ];

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'min:3',
                'max:255',
                Rule::unique('course_types', 'name')->ignore($request->get('course_type_uid'), 'uid'),
            ],
            'description' => 'nullable',
            'course_type_uid' => 'nullable|exists:course_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $courseTypeUid = $request->get('course_type_uid');
        $name = $request->get('name');
        $description = $request->get('description');

        if ($courseTypeUid) {
            $courseType = CourseTypesModel::find($courseTypeUid);
            $isNew = false;
        } else {
            $courseType = new CourseTypesModel();
            $courseType->uid = generateUuid();
            $isNew = true;
        }

        $courseType->name = $name;
        $courseType->description = $description;

        DB::transaction(function () use ($courseType) {
            $courseType->save();

            LogsController::createLog('Guardar tipo de curso: ' . $courseType->name, 'Tipos de cursos', auth()->user()->uid);
        });

        // Obtenemos todas los tipos
        $courseTypes = CourseTypesModel::get()->toArray();

        return response()->json([
            'message' => ($isNew) ? 'Tipo de curso añadido correctamente' : 'Tipo de curso actualizado correctamente',
            'course_types' => $courseTypes
        ], 200);
    }

    public function deleteCourseTypes(Request $request)
    {

        $uids = $request->input('uids');

        $existsCourses = CoursesModel::whereIn('course_type_uid', $uids)->exists();

        if ($existsCourses) {
            throw new OperationFailedException("No se pueden eliminar los tipos de curso porque están asociados a cursos", 406);
        }

        $courseTypes = CourseTypesModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($courseTypes) {
            foreach ($courseTypes as $courseType) {
                $courseType->delete();
                LogsController::createLog('Eliminar tipo de curso: ' . $courseType->name, 'Tipos de cursos', auth()->user()->uid);
            }
        });

        $courseTypes = CourseTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de curso eliminados correctamente', 'course_types' => $courseTypes], 200);
    }

    private function checkAccessManagers()
    {
        $user = Auth::user();

        $rolesUser = $user->roles->pluck('code')->toArray();

        $generalOptions = app('general_options');

        // Aplicable si sólo tiene el rol de gestor
        if (empty(array_diff($rolesUser, ['MANAGEMENT'])) && !$generalOptions['managers_can_manage_course_types']) {
            return false;
        }

        return true;
    }
}
