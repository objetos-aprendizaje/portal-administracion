<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\UserRolesModel;
use App\Models\UserRoleRelationshipsModel;

class CertificateAccessController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        if ($_SERVER["REDIRECT_SSL_CLIENT_VERIFY"]=="SUCCESS"){

            $user = UsersModel::where('email', strtolower($_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"]))->first();

            if ($user){

                return redirect($this->redirectWithTokenX509($user));

            }else{

                $user = new UsersModel();
                $user->uid = generate_uuid();
                $user->first_name = $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"];
                $user->last_name = $_SERVER["REDIRECT_SSL_CLIENT_S_DN_G"];
                $nif_temp = explode(" - ",$_SERVER["SSL_CLIENT_S_DN_CN"]);
                $user->nif = $nif_temp[1];
                $user->email = $_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"];
                $user->logged_x509 = 1;
                $user->save();

                $rol = UserRolesModel::where("code", "TEACHER")->first();
                $rol_relation = new UserRoleRelationshipsModel();
                $rol_relation->uid = generate_uuid();
                $rol_relation->user_uid = $user->uid;
                $rol_relation->user_role_uid = $rol;
                $rol_relation->save();

                $user = UsersModel::where('email', strtolower($_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"]))->first();

                return redirect($this->redirectWithTokenX509($user));

            }

        }else{

            return redirect(env('DOMINIO_PRINCIPAL')."/login?e=certificate-error");

        }
    }
    private function redirectWithTokenX509($user){

        $user->token_x509 = generateToken();
        $user->save();
        $url = env('DOMINIO_PRINCIPAL')."/token_login/".$user->token_x509;
        return $url;

    }
}
