<?php

namespace App\Http\Controllers;

use App\Models\GeneralOptionsModel;
use App\Models\Saml2TenantsModel;
use App\Models\UsersAccessesModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class LoginController extends BaseController
{
    public function index()
    {

        $logo_bd = GeneralOptionsModel::where('option_name', 'poa_logo')->first();

        if ($logo_bd != null) $logo = $logo_bd['option_value'];
        else $logo = null;


        $loginCas = GeneralOptionsModel::where('option_name', 'cas_active')->first();
        $loginCasUrl = Saml2TenantsModel::where('key', 'cas')->first();

        if ($loginCas['option_value'] == 1) {
            $urlCas = url('saml2/' . $loginCasUrl->uuid . '/login');
        } else $urlCas = false;

        $loginRediris = GeneralOptionsModel::where('option_name', 'rediris_active')->first();
        $loginRedirisUrl = Saml2TenantsModel::where('key', 'rediris')->first();

        if ($loginRediris['option_value'] == 1) {
            $urlRediris = url('saml2/' . $loginRedirisUrl->uuid . '/login');
        } else $urlRediris = false;

        return view('non_authenticated.login', [
            "page_name" => "Inicia sesión",
            "page_title" => "Inicia sesión",
            "logo" => $logo,
            "resources" => [
                "resources/js/login.js"
            ],
            "cert_login" => env('DOMINIO_CERTIFICADO'),
            "urlCas" => $urlCas,
            "urlRediris" => $urlRediris,
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            $this->loginUser($credentials['email']);
        } catch (\Exception $e) {
            return response()->json(['authenticated' => false, 'error' => $e->getMessage()])->setStatusCode(401);
        }

        $user = $this->getUser($credentials['email']);

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user);

            $this->saveUserAccess($user);

            return response()->json(['authenticated' => true]);
        } else {
            return response()->json(['authenticated' => false, 'error' => 'No se ha encontrado ninguna cuenta con esas credenciales'])->setStatusCode(401);
        }
    }

    public function loginCertificate()
    {
        $data = $_GET['data'];
        $dataParsed = json_decode($data);
        $expiration = $_GET['expiration'];
        $hash = $_GET['hash'];

        $hashCheck = md5($data . $expiration . env('KEY_CHECK_CERTIFICATE_ACCESS'));

        if ($expiration < time() || $hash != $hashCheck) return redirect('/login');

        $user = UsersModel::with('roles')
            ->whereRaw('LOWER(nif) = ?', [strtolower($dataParsed->nif)])
            ->whereHas('roles', function ($query) {
                $query->whereIn('code', ['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']);
            })
            ->first();


        if (!$user || !$user->verified) return redirect('/login?e=certificate-error');

        if (!$user->identity_verified) {
            $user->identity_verified = true;
            $user->save();
        }

        Auth::login($user);
        return redirect('/');
    }

    public function handleSocialCallback($loginMethod)
    {
        $this->validateLoginMethod($loginMethod);

        $userSocialLogin = Socialite::driver($loginMethod)->user();

        try {
            $this->loginUser($userSocialLogin->email);
        } catch (\Exception $e) {
            return redirect('login')->withErrors($e->getMessage());
        }

        return redirect('/');
    }

    public function redirectToSocialLogin($loginMethod)
    {
        $this->validateLoginMethod($loginMethod);
        return Socialite::driver($loginMethod)->redirect();
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();

        $url_logout = env('APP_URL') . "/login";

        return redirect($url_logout);
    }

    private function loginUser($email)
    {
        $user = $this->getUser($email);
        $this->saveUserAccess($user);
        Auth::login($user);
    }

    private function getUser($email)
    {
        $user = UsersModel::with('roles')
            ->whereHas('roles', function ($query) {
                $query->whereIn('code', ['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']);
            })
            ->where('email', $email)
            ->first();

        if (!$user) {
            throw new \Exception('No hay ninguna cuenta asociada al email');
        }

        return $user;
    }

    private function saveUserAccess($user)
    {
        UsersAccessesModel::insert([
            'uid' => generate_uuid(),
            'user_uid' => $user->uid,
            'date' => date('Y-m-d H:i:s')
        ]);
    }

    private function validateLoginMethod($loginMethod)
    {
        if (!in_array($loginMethod, ['google', 'twitter', 'facebook', 'linkedin-openid'])) {
            throw new \Exception('Método de login no válido');
        }
    }
}
