<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CertificateTestAccessController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        if ($_SERVER["REDIRECT_SSL_CLIENT_VERIFY"]=="SUCCESS"){

            $user = UsersModel::where('email', strtolower($_SERVER["REDIRECT_SSL_CLIENT_SAN_Email_0"]))->first();

            Auth::login($user);

            return Redirect::to('/');

        }else{

            dd("Mal configurado, no pide certificado");

        }
    }

}
