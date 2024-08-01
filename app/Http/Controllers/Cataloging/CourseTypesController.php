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
            if (!$this->checkAccessManagers()) abort(403);
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

        $course_types = CourseTypesModel::get()->toArray();

        return view(
            'cataloging.course_types.index',
            [
                "page_name" => "Tipos de curso",
                "page_title" => "Tipos de curso",
                "resources" => [
                    "resources/js/cataloging_module/course_types.js"
                ],
                "course_types" => $course_types,
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
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
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
    public function getCourseType($course_type_uid)
    {

        if (!$course_type_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $course_type = CourseTypesModel::where('uid', $course_type_uid)->first();

        if (!$course_type) {
            return response()->json(['message' => 'El tipo de curso no existe'], 406);
        }

        return response()->json($course_type, 200);
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
                'required', 'min:3', 'max:255',
                Rule::unique('course_types', 'name')->ignore($request->get('course_type_uid'), 'uid'),
            ],
            'description' => 'nullable',
            'course_type_uid' => 'nullable|exists:course_types,uid',
        ], $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $isNew = true;

        $course_type_uid = $request->get('course_type_uid');
        $name = $request->get('name');
        $description = $request->get('description');

        if ($course_type_uid) {
            $course_type = CourseTypesModel::find($course_type_uid);
            $isNew = false;
        } else {
            $course_type = new CourseTypesModel();
            $course_type->uid = generate_uuid();
            $isNew = true;
        }

        $course_type->name = $name;
        $course_type->description = $description;

        DB::transaction(function () use ($course_type) {
            $course_type->save();
            LogsController::createLog('Guardar tipo de curso', 'Tipos de cursos', auth()->user()->uid);
        });

        // Obtenemos todas los tipos
        $course_types = CourseTypesModel::get()->toArray();

        return response()->json([
            'message' => ($isNew) ? 'Tipo de curso añadido correctamente' : 'Tipo de curso actualizado correctamente',
            'course_types' => $course_types
        ], 200);
    }

    public function deleteCourseTypes(Request $request)
    {

        $uids = $request->input('uids');

        $existsCourses = CoursesModel::whereIn('course_type_uid', $uids)->exists();

        if ($existsCourses) {
            throw new OperationFailedException("No se pueden eliminar los tipos de curso porque están asociados a cursos", 406);
        }

        DB::transaction(function () use ($uids) {
            CourseTypesModel::destroy($uids);
            LogsController::createLog('Eliminar tipo de curso', 'Tipos de cursos', auth()->user()->uid);
        });

        $course_types = CourseTypesModel::get()->toArray();

        return response()->json(['message' => 'Tipos de curso eliminados correctamente', 'course_types' => $course_types], 200);
    }

    private function checkAccessManagers()
    {
        $user = Auth::user();

        $roles_user = $user->roles->pluck('code')->toArray();

        $general_options = app('general_options');

        // Aplicable si sólo tiene el rol de gestor
        if (empty(array_diff($roles_user, ['MANAGEMENT'])) && !$general_options['managers_can_manage_course_types']) {
            return false;
        }

        return true;
    }
}
