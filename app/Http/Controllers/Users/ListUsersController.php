<?php

namespace App\Http\Controllers\Users;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\DepartmentsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Jobs\SendEmailJob;
use App\Rules\NifNie;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class ListUsersController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $departments = DepartmentsModel::get();

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
                "departments" => $departments
            ]
        );
    }

    public function getUserRoles()
    {
        $user_roles = UserRolesModel::orderByRaw("
            CASE
                WHEN code = 'ADMINISTRATOR' THEN 1
                WHEN code = 'MANAGEMENT' THEN 2
                WHEN code = 'TEACHER' THEN 3
                WHEN code = 'STUDENT' THEN 4
                ELSE 5
            END
        ")->get()->toArray();

        return response()->json($user_roles, 200);
    }
    public function getDepartments()
    {
        $departments = DepartmentsModel::get()->toArray();

        return response()->json($departments, 200);
    }

    public function searchUsers($search)
    {
        $users_query = UsersModel::query();

        if ($search) {
            $users_query->where(function ($query) use ($search) {
                $query->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('nif', 'ILIKE', "%{$search}%");
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
                $query->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('nif', 'ILIKE', "%{$search}%");
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

        $user = UsersModel::where('uid', $user_uid)->with('roles', 'department')->first();

        if (!$user) {
            return response()->json(['message' => 'El usuario no existe'], 406);
        }

        return response()->json($user, 200);
    }

    public function saveUser(Request $request)
    {

        $validateErrors = $this->validateUser($request);

        if ($validateErrors->any()) {
            return response()->json(['errors' => $validateErrors], 400);
        }

        $user_uid = $request->input('user_uid');

        if ($user_uid) {
            $isNew = false;
            $user = UsersModel::where('uid', $user_uid)->first();
        } else {
            $isNew = true;
            $user = new UsersModel();
            $user->uid = generate_uuid();
        }

        $user->fill($request->only([
            'first_name',
            'last_name',
            'nif',
            'email',
            'curriculum',
            'department_uid'
        ]));

        $photoFile = $request->file('photo_path');
        if ($photoFile) $this->savePhotoUser($request->file('photo_path'), $user);

        DB::transaction(function () use ($request, $user, $isNew) {

            $user->save();
            $this->syncUserRoles($request, $user);

            $messageLog = $isNew ? 'Usuario añadido' : 'Usuario actualizado';
            LogsController::createLog($messageLog, 'Usuarios', auth()->user()->uid);

            // Enviar notificación de restablecimiento de contraseña si es un nuevo usuario
            if ($isNew) {
                $this->sendEmailResetPassword($user);
            }
        }, 5);

        return response()->json(['message' => $isNew ? 'Se ha creado el usuario correctamente' : 'Se ha actualizado el usuario correctamente'], 200);
    }

    private function validateUser($request)
    {

        $messages = [
            'first_name.required' => 'Introduce el nombre del usuario.',
            'last_name.required' => 'Introduce los apellidos del usuario.',
            'nif.required' => 'Introduce el NIF del usuario.',
            'nif.unique' => 'Este NIF ya está registrado.',
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
            $rules['nif'] = 'required|unique:users,nif,' . $user_uid . ',uid';
        } else {
            $rules['email'] = 'required|email|unique:users,email';
            $rules['nif'] = 'required|unique:users,nif';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator->errors();
    }

    private function syncUserRoles($request, $user)
    {
        $roles = $request->input('roles');
        $roles = json_decode($roles, true);

        $roles_bd = UserRolesModel::whereIn('uid', $roles)->get()->pluck('uid');

        $roles_to_sync = [];

        foreach ($roles_bd as $rol_uid) {
            $roles_to_sync[] = [
                'uid' => generate_uuid(),
                'user_uid' => $user->uid,
                'user_role_uid' => $rol_uid
            ];
        }

        $user->roles()->sync($roles_to_sync);
    }

    private function savePhotoUser($file, $user)
    {
        $path = 'images/users-images';
        $destinationPath = public_path($path);
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();

        $filename = "{$originalName}-{$timestamp}.{$extension}";

        $file->move($destinationPath, $filename);

        $user->photo_path = $path . "/" . $filename;
    }

    private function sendEmailResetPassword($user)
    {
        // Generar el token de restablecimiento de contraseña
        $token = md5(uniqid(rand(), true));
        $minutes_expiration_token = env('PWRES_TOKEN_EXPIRATION_MIN', 60);
        $expiration_date = date("Y-m-d H:i:s", strtotime("+$minutes_expiration_token minutes"));

        // Insertar el token en la tabla password_reset_tokens
        DB::table('reset_password_tokens')->insert([
            'uid' => generate_uuid(),
            'uid_user' => $user->uid,
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
            'expiration_date' => $expiration_date,
        ]);

        $isStudent = $user->hasAnyRole(['STUDENT']);

        $url = $this->generateUrlRestorePassword($isStudent, $token, $user);

        $parameters = [
            'url' => $url
        ];

        dispatch(new SendEmailJob($user->email, 'Restablecer contraseña', $parameters, 'emails.set_password_new_account'));
    }

    // Si tiene el rol de estudiante, lo mandamos al front. Si no tiene estudiante lo mandamos al back
    private function generateUrlRestorePassword($isStudent, $token, $user)
    {

        $originalUrl = config('app.url');

        if ($isStudent) {
            URL::forceRootUrl(env('FRONT_URL'));
        }

        $url = URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(config('auth.passwords.users.expire')),
            ['token' => $token, 'email' => $user->email]
        );

        URL::forceRootUrl($originalUrl);

        return $url;
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
                $query->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('nif', 'ILIKE', "%{$search}%");
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
                $query->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('nif', 'ILIKE', "%{$search}%");
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
                $query->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('nif', 'ILIKE', "%{$search}%");
            })
            ->whereDoesntHave('EducationalProgramsStudents', function ($query) use ($course) {
                $query->where('educational_program_uid', $course);
            });

        $users = $users_query->limit(5)->get()->toArray();


        return response()->json($users, 200);
    }
}
