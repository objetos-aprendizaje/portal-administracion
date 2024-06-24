<?php

namespace App\Http\Controllers\Users;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Rules\NifNie;


class ListUsersController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        return view(
            "users.list_users.index",
            [
                "page_name" => "Listado de usuarios",
                "page_title" => "Listado de usuarios",
                "resources" => [
                    "resources/js/users_module/list_users.js"
                ],
                "tabulator" => true,
                "tomselect" => true,
                "flatpickr" => true,
                "submenuselected" => "list-users",
            ]
        );
    }

    public function getUserRoles()
    {
        $user_roles = UserRolesModel::orderByRaw("FIELD(code, 'ADMINISTRATOR', 'MANAGEMENT', 'TEACHER', 'STUDENT')")
            ->get()
            ->toArray();

        return response()->json($user_roles, 200);
    }

    public function searchUsers($search)
    {
        $users_query = UsersModel::query();

        if ($search) {
            $users_query->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('nif', 'LIKE', "%{$search}%");
            });
        }

        $users = $users_query->limit(5)->get()->toArray();

        return response()->json($users, 200);
    }

    public function getUsers(Request $request)
    {
        $size = $request->get('size', 1);
        $search = $request->get('search');
        $sort = $request->get('sort');
        $filters = $request->get('filters');

        $query = UsersModel::query()->with('roles');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('nif', 'LIKE', "%{$search}%");
            });
        }

        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $order) {
                $query->orderBy($order['field'], $order['dir']);
            }
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if ($filter['database_field'] == 'creation_date') {
                    if (count($filter['value']) == 2) {
                        $query->whereBetween('created_at', [$filter['value'][0], $filter['value'][1]]);
                    }
                } else if ($filter['database_field'] == "roles") {
                    $query->whereHas('roles', function ($query) use ($filter) {
                        $query->whereIn('user_roles.uid', $filter['value']);
                    });
                }
            }
        }

        $data = $query->paginate($size);

        return response()->json($data, 200);
    }

    /**
     * Obtiene un usuario por uid
     */
    public function getUser($user_uid)
    {
        if (!$user_uid) {
            return response()->json(['message' => env('ERROR_MESSAGE')], 400);
        }

        $user = UsersModel::where('uid', $user_uid)->with('roles')->first();

        if (!$user) {
            return response()->json(['message' => 'El usuario no existe'], 406);
        }

        return response()->json($user, 200);
    }


    public function saveUser(Request $request)
    {

        $messages = [
            'first_name.required' => 'Introduce el nombre del usuario.',
            'last_name.required' => 'Introduce los apellidos del usuario.',
            'nif.required' => 'Introduce el NIF del usuario.',
            'email.required' => 'Introduce el email del usuario.',
            'email.unique' => 'Este email ya está registrado.',
            'roles.required' => 'Debe seleccionar al menos un rol',
            'photo_path.max' => 'La imagen no puede superar los 6MB'
        ];


        $rules = [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'nif' => ['required', new NifNie],
            'email' => 'required|email|unique:users,email',
            'curriculum' => 'nullable|string',
            'roles' => ['required', function ($attribute, $value, $fail) {
                $value = json_decode($value, true);
                if (!$value || empty($value)) {
                    $fail('Debes seleccionar al menos un rol');
                }
            }],
            'photo_path' => 'max:6144', // Tamaño máximo de 2MB
        ];

        $user_uid = $request->input('user_uid');

        if ($user_uid) {
            $rules['email'] = 'required|email|unique:users,email,' . $user_uid . ',uid';
        } else {
            $rules['email'] = 'required|email|unique:users,email';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $user_uid) {

            if ($user_uid) {
                $isNew = false;
                $user_bd = UsersModel::where('uid', $user_uid)->first();
                $user_uuid = $user_bd->uid;
            } else {
                $isNew = true;
                $user_bd = new UsersModel();
                $user_uuid = generate_uuid();
                $user_bd->uid = $user_uuid;
            }

            $user_bd->fill($request->only([
                'first_name', 'last_name', 'nif', 'email', 'curriculum'
            ]));

            if ($request->file('photo_path')) {
                $file = $request->file('photo_path');
                $path = 'images/users-images';
                $destinationPath = public_path($path);
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $timestamp = time();

                $filename = "{$originalName}-{$timestamp}.{$extension}";

                $file->move($destinationPath, $filename);

                $user_bd->photo_path = $path . "/" . $filename;
            }

            $user_bd->save();

            $roles = $request->input('roles');
            $roles = json_decode($roles, true);

            $roles_bd = UserRolesModel::whereIn('uid', $roles)->get()->pluck('uid');

            $roles_to_sync = [];
            foreach ($roles_bd as $rol_uid) {
                $roles_to_sync[] = [
                    'uid' => generate_uuid(),
                    'user_uid' => $user_uuid,
                    'user_role_uid' => $rol_uid
                ];
            }

            $user_bd->roles()->sync($roles_to_sync);

            $messageLog = $isNew ? 'Usuario añadido' : 'Usuario actualizado';
            LogsController::createLog($messageLog, 'Usuarios', auth()->user()->uid);

            return response()->json(['message' => $isNew ? 'Se ha creado el usuario correctamente' : 'Se ha actualizado el usuario correctamente'], 200);
        }, 5);
    }

    public function exportUsers()
    {
        $users = UsersModel::with('roles')->get()->toArray();

        // Exportamos a CSV
        $temp = tmpfile();
        $tempPath = stream_get_meta_data($temp)['uri'];

        $output = fopen($tempPath, 'w');
        fputcsv($output, array('Nombre', 'Apellidos', 'NIF', 'Email', 'Roles'));

        foreach ($users as $user) {
            $roles = [];
            foreach ($user['roles'] as $role) {
                $roles[] = $role['code'];
            }
            $roles = implode(', ', $roles);
            fputcsv($output, array($user['first_name'], $user['last_name'], $user['nif'], $user['email'], $roles));
        }

        $filename = 'usuarios_' . date('YmdHis') . '.csv';

        // Mueve el archivo temporal a la carpeta public
        $publicPath = 'downloads_temps/' . $filename;

        fflush($output);
        rename($tempPath, public_path($publicPath));

        fclose($output);

        LogsController::createLog("Exportación de usuarios", 'Usuarios', auth()->user()->uid);

        // Devuelve la URL de descarga del archivo
        return response()->json(['downloadUrl' => asset($publicPath)]);
    }

    /**
     * Recibe un array de uids de usuarios y los elimina
     */
    public function deleteUsers(Request $request)
    {

        $users_uids = $request->input('usersUids');

        DB::transaction(function () use ($users_uids) {
            UsersModel::whereIn('uid', $users_uids)->delete();
            LogsController::createLog("Eliminación de usuarios", 'Usuarios', auth()->user()->uid);
        });

        return response()->json(['message' => 'Se han eliminado los usuarios correctamente'], 200);
    }

    public function searchUsersBackend($search)
    {
        $users_query = UsersModel::query()->with('roles')
            ->whereHas('roles', function ($query) {
                $query->whereIn('code', ['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']);
            });

        if ($search) {
            $users_query->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('nif', 'LIKE', "%{$search}%");
            });
        }

        $users = $users_query->limit(5)->get()->toArray();

        return response()->json($users, 200);
    }

    public function searchUsersNoEnrolled($course, $search)
    {

        $users_query = UsersModel::query()->with('roles')
            ->whereHas('roles', function ($query) {
                $query->whereIn('code', ['STUDENT']);
            })
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('nif', 'LIKE', "%{$search}%");
            })
            ->whereDoesntHave('coursesStudents', function ($query) use ($course) {
                $query->where('course_uid', $course);
            });

        $users = $users_query->limit(5)->get()->toArray();


        return response()->json($users, 200);
    }

    public function searchUsersNoEnrolledEducationalProgram($course, $search)
    {

        $users_query = UsersModel::query()->with('roles')
            ->whereHas('roles', function ($query) {
                $query->whereIn('code', ['STUDENT']);
            })
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('nif', 'LIKE', "%{$search}%");
            })
            ->whereDoesntHave('EducationalProgramsStudents', function ($query) use ($course) {
                $query->where('educational_program_uid', $course);
            });

        $users = $users_query->limit(5)->get()->toArray();


        return response()->json($users, 200);
    }
}
