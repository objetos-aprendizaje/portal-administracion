<?php

namespace App\Http\Controllers;

use App\Models\GeneralOptionsModel;
use App\Models\ResetPasswordTokensModel;
use App\Models\UsersModel;
use App\Services\MailService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;


class RecoverPasswordController extends BaseController
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function index()
    {

        $logo_bd = GeneralOptionsModel::where('option_name', 'poa_logo')->first();

        if ($logo_bd != null) $logo = $logo_bd['option_value'];
        else $logo = null;

        return view('non_authenticated.recover_password', [
            "page_name" => "Restablecer contraseña",
            "page_title" => "Restablecer contraseña",
            "logo" => $logo,
            "resources" => [
                "resources/js/recover_password.js",
            ]
        ]);
    }

    public function recoverPassword(Request $request)
    {
        $email = $request->input('email');

        $user = UsersModel::where('email', $email)->first();

        if ($user) {
            $minutes_expiration_token = env('MINUTES_EXPIRATION_TOKEN', 60);
            $expiration_date = date("Y-m-d H:i:s", strtotime("+$minutes_expiration_token minutes"));

            // invalidamos todos los tokens que estén en vigor para este usuario
            ResetPasswordTokensModel::where('uid_user', $user->uid)->where('expiration_date', '<', $expiration_date)->update(['expiration_date' => date("Y-m-d H:i:s")]);

            $reset_password_token = new ResetPasswordTokensModel();
            $reset_password_token->uid = generate_uuid();
            $reset_password_token->uid_user = $user->uid;
            $token = md5(uniqid(rand(), true));
            $reset_password_token->token = $token;
            $reset_password_token->expiration_date = $expiration_date;
            $reset_password_token->save();

            $this->sendEmailResetPassword($user, $token);
        }

        return response()->json(['reset' => true, 'message' => 'Se ha enviado un email para restablecer la contraseña']);
    }

    public function resetPassword($token) {

        $reset_password_token = ResetPasswordTokensModel::where('token', $token)->first();

        // Comprobamos si ha expirado
        $date_expiration_token = $reset_password_token->expiration_date;
        $actual_date = date("Y-m-d H:i:s");


        $logo_bd = GeneralOptionsModel::where('option_name', 'poa_logo')->first();

        if ($logo_bd != null) $logo = $logo_bd['option_value'];
        else $logo = null;


        if($date_expiration_token > $actual_date) {

            return view('non_authenticated.reset_password', [
                "page_name" => "Restablecer contraseña",
                "page_title" => "Restablecer contraseña",
                "token" => $token,
                "logo" => $logo,
                "resources" => [
                    "resources/js/reset_password.js",
                ]
            ]);
        }

    }

    private function sendEmailResetPassword($user, $token)
    {
        $url = env('APP_URL') . '/reset_password/' . $token;

        $data = [
            'name' => $user->first_name . ' ' . $user->last_name,
            'url' => $url,
        ];

        $this->mailService->sendResetPasswordMail($user, $data);

    }
}
