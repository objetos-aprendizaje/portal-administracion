<?php

namespace App\Http\Controllers;

use App\Models\GeneralOptionsModel;
use App\Models\ResetPasswordTokensModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends BaseController
{
    public function index($token)
    {

        $reset_password_token = ResetPasswordTokensModel::where('token', $token)->first();

        if(!$reset_password_token) {
            return redirect()->route('recover-password')->with('error', 'El token no es válido');
        }
        $actual_date = date("Y-m-d H:i:s");

        $logo_bd = GeneralOptionsModel::where('option_name', 'poa_logo')->first();

        if ($logo_bd != null) $logo = $logo_bd['option_value'];
        else $logo = null;

        if($reset_password_token->expiration_date > $actual_date) {
            return view('non_authenticated.reset_password', [
                "page_name" => "Restablecer contraseña",
                "page_title" => "Restablecer contraseña",
                "token" => $token,
                "logo" => $logo,
                "resources" => [
                    "resources/js/reset_password.js",
                ]
            ]);
        } else {

            return redirect()->route('recover-password')->with('error', 'El token ha expirado. Vuelva a solicitar el restablecimiento de la contraseña.');
        }
    }


    public function resetPassword(Request $request)
    {
        $messages = [
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener como mínimo 8 caracteres',
        ];

        $validator = Validator::make($request->all(), ['password' => 'required|min:8'], $messages);

        if ($validator->fails()) {
            return response()->json(['reset' => false, 'message' => $validator->errors()->first()]);
        }

        $token = $request->input('token');
        $password = $request->input('password');

        $reset_password_token = ResetPasswordTokensModel::where('token', $token)->first();

        if(!$reset_password_token) {
            return response()->json(['reset' => false, 'message' => 'El token no es válido']);
        }

        $actual_date = date("Y-m-d H:i:s");

        if($reset_password_token->expiration_date > $actual_date) {
            DB::transaction(function () use ($reset_password_token, $password, $actual_date) {
                $user = UsersModel::where('uid', $reset_password_token->uid_user)->first();
                $user->password = password_hash($password, PASSWORD_BCRYPT);
                $user->save();

                // Invalidamos el token añadiendo la fecha de expiracion actual
                $reset_password_token->expiration_date = $actual_date;
                $reset_password_token->save();
            });

            return response()->json(['reset' => true, 'message' => 'Se ha restablecido la contraseña']);
        } else {
            return response()->json(['reset' => false, 'message' => 'El token ha expirado. Vuelva a solicitar el restablecimiento de la contraseña.']);
        }
    }
}
