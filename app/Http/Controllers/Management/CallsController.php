<?php

namespace App\Http\Controllers\Management;

use App\Models\CallsEducationalProgramTypesModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EducationalProgramTypesModel;
use App\Models\CallsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$this->checkAccessCalls() || !$this->checkManagersAccessCalls()) abort(403);
            return $next($request);
        })->except('index');
    }
    /**
     * Muestra la vista del índice de convocatorias.
     */
    public function index()
    {

        if (!$this->checkAccessCalls()) {
            return view('access_not_allowed', [
                'title' => 'Las convocatorias están desactivadas',
                'description' => 'El administrador ha desactivado el funcionamiento por convocatorias en la plataforma.'
            ]);
        }

        if (!$this->checkManagersAccessCalls()) {
            return view('access_not_allowed', [
                'title' => 'No tienes permiso para modificar las convocatorias',
                'description' => 'El administrador ha desactivado la administración de convocatorias para los gestores.'
            ]);
        }

        $educational_program_types = EducationalProgramTypesModel::all()->toArray();

        return view(
            'management.calls.index',
            [
                "page_name" => "Convocatorias",
                "page_title" => "Convocatorias",
                "resources" => [
                    "resources/js/management_module/calls.js"
                ],
                "educational_program_types" => $educational_program_types,
                "tomselect" => true,
                "tabulator" => true

            ]
        );
    }

    /**
     * Obtiene una convocatoria específica basada en su UID.
     *
     * @param  string $call_uid El UID de la convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCall($call_uid)
    {
        $call = CallsModel::where('uid', $call_uid)->with('educationalProgramTypes')->first()->toArray();
        return response()->json($call);
    }

    /**
     * Crea una nueva convocatoria.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCall(Request $request)
    {

        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato correcto.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato correcto.',
            'program_types.required' => 'Debe seleccionar al menos un tipo de programa formativo.',
            'program_types.required' => 'Debe seleccionar al menos un tipo de programa formativo.',
        ];


        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|date_format:Y-m-d\TH:i',
            'program_types' => 'required|array',
        ], $messages);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $call_uid = $request->input("call_uid");

        return DB::transaction(function () use ($request, $call_uid) {

            if (!$call_uid) {
                $call = new CallsModel();
                $call_uid = generate_uuid();
                $call->uid = $call_uid;
                $isNew = true;
            } else {
                $call = CallsModel::find($call_uid);
                $isNew = false;
            }

            $call->fill($request->only([
                'name', 'description', 'start_date', 'end_date',
            ]));

            // Para el archivo adjunto
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                // Aquí puedes guardar el archivo como lo necesites
                $uniqueName = time() . '_' . $file->getClientOriginalName();

                // Guardar el archivo en la carpeta public/attachments
                $file->move(public_path('attachments'), $uniqueName);

                $call->attachment_path = 'attachments/' . $uniqueName;
            }

            $call->save();

            $educational_programs_types_associated = $request->input('program_types');

            // Si estamos editando, podemos usar el método sync, si estamos creando debemos insertar los registros directamente
            if ($isNew) {
                foreach ($educational_programs_types_associated as $program_type) {
                    CallsEducationalProgramTypesModel::create([
                        "uid" => generate_uuid(),
                        "call_uid" => $call_uid,
                        "educational_program_type_uid" => $program_type
                    ]);
                }
            } else {
                // Sincronización de los tipos de programas formativos asociados
                $syncData = [];
                if (!empty($educational_programs_types_associated)) {
                    foreach ($educational_programs_types_associated as $program_type) {
                        $syncData[$program_type] = ['uid' => generate_uuid()];
                    }
                }
                $call->educationalProgramTypes()->sync($syncData);
            }

            return response()->json(['message' => $isNew ? 'Convocatoria añadida correctamente' : 'Convocatoria actualizada correctamente']);
        }, 5);
    }

    /**
     * Elimina una convocatoria específica.
     *
     * @param  string $call_uid El UID de la convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCalls(Request $request)
    {
        $uids = $request->input('uids');
        CallsModel::destroy($uids);

        return response()->json(['message' => 'Convocatorias eliminadas correctamente']);
    }

    /**
     * Obtiene todas las convocatorias.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCalls(Request $request)
    {

        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = CallsModel::query();

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    // Comprueba si las convocatorias están activadas a nivel general
    private function checkAccessCalls()
    {
        $general_options = app('general_options');

        if (!$general_options['operation_by_calls']) return false;

        return true;
    }

    // Comprueba si el usuario es sólo gestor y si tiene permiso para gestionar las convocatorias
    private function checkManagersAccessCalls()
    {
        $user = Auth::user();

        $roles_user = $user->roles->pluck('code')->toArray();

        $general_options = app('general_options');

        // Aplicable si sólo tiene el rol de gestor
        if (empty(array_diff($roles_user, ['MANAGEMENT'])) && !$general_options['managers_can_manage_calls']) {
            return false;
        }

        return true;
    }
}
