<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationFailedException;
use App\Models\GeneralOptionsModel;
use App\Models\ResetPasswordTokensModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends BaseController
{

    public function index(Request $request, $token = null)
    {
        return view('non_authenticated.reset_password', [
            "resources" => [
                "resources/js/reset_password.js",
            ]
        ])->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    public function resetPassword(Request $request)
    {
        $messages = [
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener como mínimo 8 caracteres',
        ];

        $validator = Validator::make($request->all(), ['password' => 'required|min:8'], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $token = $request->input('token');
        $password = $request->input('password');

        $reset_password_token = ResetPasswordTokensModel::where('token', $token)->first();

        if (!$reset_password_token) {
            return redirect()->route('login')->with(['reset' => false, 'message' => 'El token no es válido']);
        }

        $actual_date = date("Y-m-d H:i:s");

        if ($reset_password_token->expiration_date < $actual_date) {
            return redirect()->route('login')->withErrors(['Ha expirado el tiempo para restablecer la contraseña. Por favor, solicítelo de nuevo']);
        }

        DB::transaction(function () use ($reset_password_token, $password, $actual_date) {
            $user = UsersModel::where('uid', $reset_password_token->uid_user)->first();
            $user->password = password_hash($password, PASSWORD_BCRYPT);
            $user->save();

            // Invalidamos el token añadiendo la fecha de expiracion actual
            $reset_password_token->expiration_date = $actual_date;
            $reset_password_token->save();
        });

        return redirect()->route('login')->with([
            'success' => ['Se ha restablecido la contraseña'],
        ]);
    }
}
