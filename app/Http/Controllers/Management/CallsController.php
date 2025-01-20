<?php

namespace App\Http\Controllers\Management;

use App\Models\CallsEducationalProgramTypesModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\EducationalProgramTypesModel;
use App\Models\CallsModel;
use App\Models\CoursesModel;
use App\Models\EducationalProgramsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$this->checkAccessCalls() || !$this->checkManagersAccessCalls()) {
                abort(403);
            }
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
                'description' => 'El administrador ha desactivado el funcionamiento por convocatorias en la plataforma.',
                "page_name" => "Convocatorias",
                "page_title" => "Convocatorias",
                "submenuselected" => "management-calls"
            ]);
        }

        if (!$this->checkManagersAccessCalls()) {
            return view('access_not_allowed', [
                'title' => 'No tienes permiso para modificar las convocatorias',
                'description' => 'El administrador ha desactivado la administración de convocatorias para los gestores.',
                "page_name" => "Convocatorias",
                "page_title" => "Convocatorias",
                "submenuselected" => "management-calls"
            ]);
        }

        $educationalProgramTypes = EducationalProgramTypesModel::all()->toArray();

        return view(
            'management.calls.index',
            [
                "page_name" => "Convocatorias",
                "page_title" => "Convocatorias",
                "resources" => [
                    "resources/js/management_module/calls.js"
                ],
                "educational_program_types" => $educationalProgramTypes,
                "tomselect" => true,
                "tabulator" => true,
                "submenuselected" => "management-calls",

            ]
        );
    }

    /**
     * Obtiene una convocatoria específica basada en su UID.
     *
     * @param  string $callUid El UID de la convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCall($callUid)
    {
        $call = CallsModel::where('uid', $callUid)->with('educationalProgramTypes')->first()->toArray();
        return response()->json($call);
    }

    private function validateCall($request, $isNew)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato correcto.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato correcto.',
            'program_types.required' => 'Debe seleccionar al menos un tipo de programa formativo.',
            'program_types.required' => 'Debe seleccionar al menos un tipo de programa formativo.',
            'start_date.after_or_equal' => 'La fecha de inicio debe ser igual o posterior a la fecha actual',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio',
        ];

        $rules = [
            'name' => 'required',
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|date_format:Y-m-d\TH:i',
            'program_types' => 'required|array',
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|after_or_equal:start_date|date_format:Y-m-d\TH:i'
        ];

        if($isNew) {
            $rules['start_date'] = 'required|after_or_equal:now|date_format:Y-m-d\TH:i';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator->errors();
    }

    /**
     * Crea una nueva convocatoria.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCall(Request $request)
    {
        $callUid = $request->input("call_uid");

        if (!$callUid) {
            $call = new CallsModel();
            $callUid = generateUuid();
            $call->uid = $callUid;
            $isNew = true;
        } else {
            $call = CallsModel::find($callUid);
            $isNew = false;
        }

        $errorsValidator = $this->validateCall($request, $isNew);

        if ($errorsValidator->any()) {
            return response()->json(['errors' => $errorsValidator], 422);
        }


        return DB::transaction(function () use ($request, $isNew, $call) {

            $this->handleFileUpload($request, $call);
            $this->fillCall($request, $call);
            $this->handleEducationalProgramTypes($request, $call, $isNew);
            $this->logAction();

            return response()->json(['message' => $isNew ? 'Convocatoria añadida correctamente' : 'Convocatoria actualizada correctamente']);
        }, 5);
    }

    private function handleFileUpload($request, $call)
    {
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $uniqueName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('attachments'), $uniqueName);
            $call->attachment_path = 'attachments/' . $uniqueName;
        }
    }

    private function fillCall($request, $call)
    {
        $call->fill($request->only([
            'name', 'description', 'start_date', 'end_date',
        ]));
        $call->save();
    }

    private function handleEducationalProgramTypes($request, $call, $isNew)
    {
        $educationalProgramsTypesAssociated = $request->input('program_types');

        if ($isNew) {
            foreach ($educationalProgramsTypesAssociated as $programType) {
                CallsEducationalProgramTypesModel::create([
                    "uid" => generateUuid(),
                    "call_uid" => $call->uid,
                    "educational_program_type_uid" => $programType
                ]);
            }
        } else {
            $syncData = [];
            if (!empty($educationalProgramsTypesAssociated)) {
                foreach ($educationalProgramsTypesAssociated as $programType) {
                    $syncData[$programType] = ['uid' => generateUuid()];
                }
            }
            $call->educationalProgramTypes()->sync($syncData);
        }
    }

    private function logAction()
    {
        LogsController::createLog('Añadir convocatoria', 'Convocatorias', auth()->user()->uid);
    }

    /**
     * Elimina una convocatoria específica.
     *
     * @param  string $callUid El UID de la convocatoria.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCalls(Request $request)
    {
        $uidsCalls = $request->input('uids');

        // Comprobamos si hay convocatorias asociadas a los programas formativos
        $existCourses = CoursesModel::whereIn('call_uid', $uidsCalls)->exists();
        $existEducationalPrograms = EducationalProgramsModel::whereIn('call_uid', $uidsCalls)->exists();

        if ($existCourses || $existEducationalPrograms) {
            return response()->json(['message' => 'No se pueden eliminar las convocatorias porque están asociadas a cursos o programas formativos.'], 422);
        }

        DB::transaction(function () use ($uidsCalls) {
            CallsModel::destroy($uidsCalls);
            LogsController::createLog('Eliminar convocatoria', 'Convocatorias', auth()->user()->uid);
        });

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
            $query->where('name', 'ILIKE', "%{$search}%");
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
        $generalOptions = app('general_options');

        if (!$generalOptions['operation_by_calls']) {
            return false;
        }

        return true;
    }

    // Comprueba si el usuario es sólo gestor y si tiene permiso para gestionar las convocatorias
    private function checkManagersAccessCalls()
    {
        $user = Auth::user();

        $rolesUser = $user->roles->pluck('code')->toArray();

        $generalOptions = app('general_options');

        // Aplicable si sólo tiene el rol de gestor
        if (empty(array_diff($rolesUser, ['MANAGEMENT'])) && !$generalOptions['managers_can_manage_calls']) {
            return false;
        }

        return true;
    }
}
