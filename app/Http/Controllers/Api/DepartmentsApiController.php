<?php
namespace App\Http\Controllers\Api;

use App\Models\DepartmentsModel;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class DepartmentsApiController extends BaseController
{
    public function addDepartment(Request $request)
    {
        $departments = $request->all();

        $errorsValidation = $this->validateDepartments($departments);

        if($errorsValidation) {
            return response()->json(['errors' => $errorsValidation], 400);
        }

        $this->saveDepartments($departments);

        return response()->json(['message' => 'Departamentos añadidos correctamente'], 200);
    }

    public function getDepartments(){
        $departments = DepartmentsModel::all();

        return response()->json($departments, 200);
    }

    public function updateDepartment(Request $request, $uid)
    {
        $department = DepartmentsModel::where('uid', $uid)->first();

        if(!$department) {
            return response()->json(['message' => 'Departamento no encontrado'], 404);
        }

        $data = $request->all();

        $department->name = $data[0]['name'];
        $department->save();

        return response()->json(['message' => 'Departamento actualizado correctamente'], 200);
    }

    public function deleteDepartment($uid)
    {
        $department = DepartmentsModel::where('uid', $uid)->first();

        if(!$department) {
            return response()->json(['message' => 'Departamento no encontrado'], 404);
        }

        $department->delete();

        return response()->json(['message' => 'Departamento eliminado correctamente'], 200);
    }

    private function saveDepartments($departments) {

        foreach ($departments as $depart) {
            $department_bd = new DepartmentsModel();
            $uid = generate_uuid();
            $department_bd->uid = $uid;
            $department_bd->name = $depart['name'];
            $department_bd->save();
        }

    }

    private function validateDepartments($departments) {

        $messages = [
            "name.required" => "El campo NAME es obligatorio",
        ];

        $rules = [
            "name" => "required|string",
        ];

        foreach ($departments as $depart) {

            $validator = Validator::make($depart, $rules, $messages);

            if ($validator->fails()) {
                return $validator->errors();
            }
        }

    }

}
