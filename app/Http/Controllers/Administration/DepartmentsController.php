<?php

namespace App\Http\Controllers\Administration;

use App\Exceptions\OperationFailedException;
use App\Models\DepartmentsModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\UsersModel;

class DepartmentsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return view(
            'administration.departments.index',
            [
                "coloris" => true,
                "page_name" => "Departamentos",
                "page_title" => "Departamentos",
                "resources" => [
                    "resources/js/administration_module/departments.js"
                ],
                "tabulator" => true,
                "submenuselected" => "departments",
            ]
        );
    }

    /**
     * Obtiene todas las licencias.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartments(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');

        $query = DepartmentsModel::query();

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

    /**
     * Obtiene una licencia específico basada en su UID.
     *
     * @param  string $centerUid El UID del centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartment($departmentUid)
    {
        $department = DepartmentsModel::where('uid', $departmentUid)->first()->toArray();
        return response()->json($department);
    }

    /**
     * Crea una nueva licencia.
     *
     * @param  \Illuminate\Http\Request  $request Los datos de la nueva licencia.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveDepartment(Request $request)
    {
        $messages = [
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ], $messages);


        if ($validator->fails()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validator->errors()], 422);
        }

        $departmentUid = $request->input("department_uid");

        return DB::transaction(function () use ($request, $departmentUid) {

            if (!$departmentUid) {
                $department = new DepartmentsModel();
                $departmentUid = generateUuid();
                $department->uid = $departmentUid;
                $isNew = true;
            } else {
                $department = DepartmentsModel::find($departmentUid);
                $isNew = false;
            }

            $department->fill($request->only([
                'name',
            ]));

            $department->save();

            LogsController::createLog('Añadir departamento: ' . $department->name, 'Departamentos', auth()->user()->uid);

            return response()->json(['message' => $isNew ? 'Departamento añadida correctamente' : 'Departamento actualizada correctamente']);
        }, 5);
    }

    /**
     * Elimina un centro específico.
     *
     * @param  string $uids Array de uids de sistemas centro.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDepartments(Request $request)
    {
        $uids = $request->input('uids');

        // Comprobamos si hay cursos que estén vinculados a alguno de los centros
        $existsUsers = UsersModel::whereIn('department_uid', $uids)->exists();

        if ($existsUsers) {
            throw new OperationFailedException('No se pueden eliminar los departamentos porque hay usuarios vinculados a ellos');
        }

        $departments = DepartmentsModel::whereIn('uid', $uids)->get();
        DB::transaction(function () use ($departments) {
            foreach ($departments as $department) {
                $department->delete();
                LogsController::createLog('Eliminar departamentos: ' . $department->name, 'Departamentos', auth()->user()->uid);
            }
        }, 5);

        return response()->json(['message' => 'Departamentos eliminados correctamente']);
    }
}
