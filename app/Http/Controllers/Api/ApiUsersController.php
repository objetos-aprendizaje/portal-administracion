<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OperationFailedException;
use App\Models\UserRoleRelationshipsModel;
use App\Models\UserRolesModel;
use App\Models\UsersModel;
use App\Rules\NifNie;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiUsersController extends BaseController
{
    public function getRoles()
    {
        $userRoles = UserRolesModel::select("name", "code")->get();

        return response()->json($userRoles);
    }

    public function getUsers(Request $request)
    {
        $allowedFilters = [
            'uid',
            'first_name',
            'email',
            'last_name',
            'roles'
        ];

        $filters = $request->all();

        $filteredFilters = array_filter(
            $filters,
            fn($key) => in_array($key, $allowedFilters),
            ARRAY_FILTER_USE_KEY
        );

        $users = UsersModel::with('roles');

        foreach ($filteredFilters as $key => $value) {
            if ($key === 'roles') {
                $users->whereHas('roles', function ($query) use ($value) {
                    $query->where('code', $value);
                });
            } else {
                $users->where($key, 'ilike', '%' . $value . '%');
            }
        }

        $users = $users->get();
        $users = $users->map(function ($user) {
            return [
                "uid" => $user->uid,
                "first_name" => $user->first_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "nif" => $user->nif,
                "curriculum" => $user->curriculum,
                "roles" => $user->roles->pluck('code'),
            ];
        });

        return response()->json($users, 200);
    }

    public function updateUser(Request $request, $emailUser)
    {
        $updateData = $request->all();

        $user = UsersModel::where('email', $emailUser)->first();

        if (!$user) {
            throw new OperationFailedException("Usuario no encontrado", 404);
        }

        $fields = [
            'first_name',
            'last_name',
            'nif',
            'curriculum',
            'password',
        ];

        foreach ($fields as $field) {
            if (isset($updateData[$field])) {
                $user->{$field} = $updateData[$field];
            }
        }

        if (isset($updateData['new_email'])) {
            $user->email = $updateData['new_email'];
        }

        if (isset($updateData['password']) && $updateData['password']) {
            $user->password = password_hash($updateData['password'], PASSWORD_BCRYPT);
        }

        DB::transaction(function () use ($user, $updateData) {
            $user->save();

            if (isset($updateData['roles'])) {

                $rolesBd = UserRolesModel::whereIn('code', $updateData["roles"])->get()->pluck('uid');
                $rolesToSync = [];

                foreach ($rolesBd as $rolUid) {
                    $rolesToSync[] = [
                        'uid' => generateUuid(),
                        'user_uid' => $user->uid,
                        'user_role_uid' => $rolUid
                    ];
                }

                $user->roles()->sync($rolesToSync);
            }
        });
        return response()->json(['message' => 'Usuario actualizado correctamente'], 200);
    }

    public function registerUsers(Request $request)
    {
        $users = $request->all();

        if (!is_array($users) || !count($users)) {
            return response()->json(['errors' => ['users' => 'Debe especificar al menos un usuario']], 400);
        }

        $validationErrors = $this->validateRegisterUsers($users);

        if ($validationErrors) {
            return response()->json(['errors' => $validationErrors], 400);
        }

        $usersData = [];
        $usersRolesData = [];

        $userRoles = UserRolesModel::get()->keyBy('code');

        DB::transaction(function () use ($users, $usersData, $usersRolesData, $userRoles) {
            foreach ($users as $user) {
                $userUid = generateUuid();

                $usersData[] = [
                    'uid' => $userUid,
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'nif' => $user['nif'],
                    'email' => $user['email'],
                    'curriculum' => $user['curriculum'] ?? null,
                    'password' => isset($user['password']) && $user['password'] ? password_hash($user['password'], PASSWORD_BCRYPT) : null,
                    'created_at' => now(),
                ];

                foreach ($user["roles"] as $role) {
                    $usersRolesData[] = [
                        "uid" => generateUuid(),
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
        });

        return response()->json(['message' => 'Usuarios registrados correctamente'], 200);
    }

    private function validateRegisterUsers($users)
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
                'roles' => 'required|array|min:1',
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
