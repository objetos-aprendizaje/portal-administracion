<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\EducationalProgramTypesModel;
use App\Models\RedirectionQueriesLearningObjectsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\CourseTypesModel;

class RedirectionQueriesLearningObjectsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $educationalProgramTypes = EducationalProgramTypesModel::all()->toArray();

        $courseTypes = CourseTypesModel::all()->toArray();

        return view(
            'administration.redirection_queries_educational_program_types.index',
            [
                "page_name" => "Redirección de consultas",
                "page_title" => "Redirección de consultas",
                "resources" => [
                    "resources/js/administration_module/redirection_queries_educational_program_types.js",
                    "resources/js/modal_handler.js"
                ],
                "tabulator" => true,
                "educational_program_types" => $educationalProgramTypes,
                "courseTypes" => $courseTypes,
                "submenuselected" => "redirection-queries-educational-program-types",
            ]
        );
    }

    public function getRedirectionQuery($uidRedirectionQuery)
    {

        $redirectionQuery = RedirectionQueriesLearningObjectsModel::where('uid', $uidRedirectionQuery)->with('educational_program_type')->first();

        return response()->json($redirectionQuery, 200);
    }

    /**
     * @param {*} string $programTypeUid UID del tipo de programa educativo.
     * Obtiene el html del listado de redirecciones de consulta
     */
    public function getRedirectionsQueries(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = RedirectionQueriesLearningObjectsModel::query();

        if ($search) {
            $query->where('contact', 'ILIKE', "%{$search}%");
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
     *
     * Obtiene el html del listado de redirecciones de consulta
     */
    public function saveRedirectionQuery(Request $request)
    {

        $messages = [
            'educational_program_type_uid.required_if' => 'El tipo de programa formativo es obligatorio',
            'course_type_uid.required_if' => 'El tipo de curso es obligatorio',
            'learning_object_type.required' => 'El tipo de objeto es obligatorio',
            'type' => 'required|string|in:web,email',
            'contact.required' => 'El contacto es obligatorio',
            'contact.max' => 'El contacto es demasiado largo',
            'type.in' => 'El tipo de contacto no es válido',
            'type.required' => 'El tipo de contacto es obligatorio'
        ];

        $validator = Validator::make($request->all(), [
            'learning_object_type' => 'required|string',
            'educational_program_type_uid' => 'required_if:learning_object_type,EDUCATIONAL_PROGRAM',
            'course_type_uid' => 'required_if:learning_object_type,COURSE',
            'type' => 'required|string',
            'contact' => 'required|max:200'
        ], $messages);

        $validator->after(function ($validator) use ($request) {
            if ($request->input('type') === 'web' && !preg_match("/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/", $request->input('contact'))) {
                $validator->errors()->add('contact', 'El contacto debe ser una URL válida.');
            }
            if ($request->input('type') === 'email' && !preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $request->input('contact'))) {
                $validator->errors()->add('contact', 'El contacto debe ser un correo electrónico válido.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['message' => 'Hay campos incorrectos', 'errors' => $validator->errors()], 422);
        }

        $uidRedirectionQuery = $request->input('redirection_query_uid');

        if ($uidRedirectionQuery) {
            $redirectionQuery = RedirectionQueriesLearningObjectsModel::where('uid', $uidRedirectionQuery)->first();
        } else {
            $redirectionQuery = new RedirectionQueriesLearningObjectsModel();
            $redirectionQuery->uid = generateUuid();
        }

        $redirectionQuery->fill($request->only([
            'educational_program_type_uid', 'type', 'contact', 'learning_object_type'
        ]));

        $redirectionQueryType = $request->input('learning_object_type');

        if($redirectionQueryType == "COURSE") {
            $redirectionQuery->course_type_uid = $request->input('course_type_uid');
            $redirectionQuery->educational_program_type_uid = null;
        }
        else {
            $redirectionQuery->educational_program_type_uid = $request->input('educational_program_type_uid');
            $redirectionQuery->course_type_uid = null;
        }

        DB::transaction(function () use ($redirectionQuery) {
            $redirectionQuery->save();
            LogsController::createLog('Añadir redirección de consulta:', 'Redirección de consultas', auth()->user()->uid);
        });

        return response()->json(['message' => 'Redirección guardada correctamente']);
    }

    /**
     *
     * Elimina en base al uid una redirección de consulta
     */
    public function deleteRedirectionsQueries(Request $request)
    {
        $uidsRedirectionQueries = $request->input('uids');

        DB::transaction(function () use ($uidsRedirectionQueries) {
            RedirectionQueriesLearningObjectsModel::destroy($uidsRedirectionQueries);
            LogsController::createLog('Eliminar redirección de consulta', 'Redirección de consultas', auth()->user()->uid);
        });

        return response()->json(['message' => 'Redirecciones eliminadas correctamente']);
    }
}
