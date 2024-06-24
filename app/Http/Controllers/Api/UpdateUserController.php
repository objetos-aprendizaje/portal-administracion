<?php

namespace App\Http\Controllers\Api;

use App\Models\UserRolesModel;
use App\Models\UsersModel;
use App\Rules\NifNie;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class UpdateUserController extends BaseController
{
    public function index(Request $request)
    {
        $updateData = $request->all();

        $validationErrors = $this->validateData($updateData);

        if ($validationErrors) {
            return response()->json(['errors' => $validationErrors], 400);
        }

        $this->updateUser($updateData);

        return response()->json(['message' => 'Usuario actualizado correctamente'], 200);
    }

    private function updateUser($updateData)
    {
        $user = UsersModel::where('email', $updateData['email'])->first();

        $user->first_name = $updateData['first_name'];
        $user->last_name = $updateData['last_name'];
        $user->nif = $updateData['nif'];
        $user->curriculum = $updateData['curriculum'];

        if (isset($updateData['password']) && $updateData['password']) {
            $user->password = password_hash($updateData['password'], PASSWORD_BCRYPT);
        }

        $user->save();

        $rolesBd = UserRolesModel::whereIn('code', $updateData["roles"])->get()->pluck('uid');
        $rolesToSync = [];

        foreach ($rolesBd as $rolUid) {
            $rolesToSync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $user->uid,
                'user_role_uid' => $rolUid
            ];
        }

        $user->roles()->sync($rolesToSync);
    }

    private function validateData($updateData)
    {
        $messages = [
            'first_name.required' => 'Introduce el nombre del usuario.',
            'last_name.required' => 'Introduce los apellidos del usuario.',
            'nif.required' => 'Introduce el NIF del usuario.',
            'email.required' => 'Introduce el email del usuario.',
            'email.exists' => 'El email introducido no existe.',
            'email.unique' => 'Este email ya está registrado.',
            'roles.required' => 'Debe seleccionar al menos un rol',
            'roles.*.in' => 'El rol seleccionado no es válido',
        ];

        $rules = [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'nif' => ['required', new NifNie],
            'email' => 'required|email|exists:users,email',
            'curriculum' => 'nullable|string',
            'roles' => 'sometimes|required|array|min:1',
            'roles.*' => 'in:ADMINISTRATOR,MANAGEMENT,TEACHER,STUDENT',
            'password' => 'nullable|string',
        ];

        $validator = Validator::make($updateData, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors();
        }
    }
}
