<?php

namespace App\Http\Controllers\Api;

use App\Models\UserRoleRelationshipsModel;
use App\Models\UserRolesModel;
use App\Models\UsersModel;
use App\Rules\NifNie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterUserController extends BaseController
{
    public function index(Request $request)
    {

        $users = $request->all();

        $validationErrors = $this->validateUsers($users);

        if ($validationErrors) {
            return response()->json(['errors' => $validationErrors], 400);
        }

        DB::transaction(function () use ($users) {
            $this->registerUsers($users);
        });

        return response()->json(['message' => 'Usuarios registrados correctamente'], 200);
    }

    private function registerUsers($users)
    {
        $usersData = [];
        $usersRolesData = [];

        $userRoles = UserRolesModel::get()->keyBy('code');

        foreach ($users as $user) {
            $userUid = generate_uuid();

            $usersData[] = [
                'uid' => $userUid,
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'nif' => $user['nif'],
                'email' => $user['email'],
                'curriculum' => $user['curriculum'],
                'password' => $user['password'] ? password_hash($user['password'], PASSWORD_BCRYPT) : null,
            ];

            foreach ($user["roles"] as $role) {
                $usersRolesData[] = [
                    "uid" => generate_uuid(),
                    "user_uid" => $userUid,
                    "user_role_uid" => $userRoles[$role]->uid,
                ];
            }
        }

        $usersDataChunked = array_chunk($usersData, 500);
        foreach ($usersDataChunked as $chunk) {
            UsersModel::insert($chunk);
        }

        $usersRolesDataChunked = array_chunk($usersRolesData, 500);
        foreach ($usersRolesDataChunked as $chunk) {
            UserRoleRelationshipsModel::insert($chunk);
        }
    }

    private function validateUsers($users)
    {
        $messages = [
            'first_name.required' => 'Introduce el nombre del usuario.',
            'last_name.required' => 'Introduce los apellidos del usuario.',
            'nif.required' => 'Introduce el NIF del usuario.',
            'email.required' => 'Introduce el email del usuario.',
            'email.unique' => 'Este email ya está registrado.',
            'roles.required' => 'Debe seleccionar al menos un rol',
            'roles.*.in' => 'El rol seleccionado no es válido',
        ];

        foreach ($users as $user) {
            $rules = [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'nif' => ['required', new NifNie],
                'email' => 'required|email|unique:users,email',
                'curriculum' => 'nullable|string',
                'roles' => 'sometimes|required|array|min:1',
                'roles.*' => 'in:ADMINISTRATOR,MANAGEMENT,TEACHER,STUDENT',
                'password' => 'nullable|string',
            ];

            $validator = Validator::make($user, $rules, $messages);

            if ($validator->fails()) {
                return $validator->errors();
            }
        }
    }
}
