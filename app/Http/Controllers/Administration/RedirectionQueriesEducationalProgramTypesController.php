<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\EducationalProgramTypesModel;
use App\Models\RedirectionQueriesEducationalProgramTypesModel;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class RedirectionQueriesEducationalProgramTypesController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $educational_program_types = EducationalProgramTypesModel::all()->toArray();

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
                "educational_program_types" => $educational_program_types
            ]
        );
    }

    public function getRedirectionQuery($uid_redirection_query)
    {

        $redirection_query = RedirectionQueriesEducationalProgramTypesModel::where('uid', $uid_redirection_query)->with('educational_program_type')->first();

        return response()->json($redirection_query, 200);
    }

    /**
     * @param {*} string $program_type_uid UID del tipo de programa educativo.
     * Obtiene el html del listado de redirecciones de consulta
     */
    public function getRedirectionsQueries(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = RedirectionQueriesEducationalProgramTypesModel::query()
            ->join(
                'educational_program_types as edu_pro_types',
                'redirection_queries_educational_program_types.educational_program_type_uid',
                '=',
                'edu_pro_types.uid'
            )
            ->select([
                'redirection_queries_educational_program_types.*',
                'edu_pro_types.name as educational_program_type_name'
            ]);

        if ($search) {
            $query->where('contact', 'LIKE', "%{$search}%");
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
            'educational_program_type_uid.required' => 'El tipo de programa educativo es obligatorio.',
            'type' => 'required|string|in:web,email',
            'contact.required' => 'El contacto es obligatorio.',
            'contact.max' => 'El contacto es demasiado largo.',
        ];

        $validator = Validator::make($request->all(), [
            'educational_program_type_uid' => 'required|string',
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uid_redirection_query = $request->input('redirection_query_uid');

        if ($uid_redirection_query) {
            $redirection_query = RedirectionQueriesEducationalProgramTypesModel::where('uid', $uid_redirection_query)->first();
        } else {
            $redirection_query = new RedirectionQueriesEducationalProgramTypesModel();
            $redirection_query->uid = generate_uuid();
        }

        $redirection_query->fill($request->only([
            'educational_program_type_uid', 'type', 'contact'
        ]));

        DB::transaction(function () use ($redirection_query) {
            $redirection_query->save();
            LogsController::createLog('Añadir redirección de consulta', 'Redirección de consultas', auth()->user()->uid);
        });

        return response()->json(['message' => 'Redirección guardada correctamente']);
    }

    /**
     *
     * Elimina en base al uid una redirección de consulta
     */
    public function deleteRedirectionsQueries(Request $request)
    {
        $uids_redirection_queries = $request->input('uids');


        DB::transaction(function () use ($uids_redirection_queries) {
            RedirectionQueriesEducationalProgramTypesModel::destroy($uids_redirection_queries);
            LogsController::createLog('Eliminar redirección de consulta', 'Redirección de consultas', auth()->user()->uid);
        });

        return response()->json(['message' => 'Redirección eliminada correctamente']);
    }
}
