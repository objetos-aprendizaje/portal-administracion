<?php

namespace App\Http\Controllers;

use App\Models\GeneralOptionsModel;
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

        return view('non_authenticated.login', [
            "page_name" => "Inicia sesión",
            "page_title" => "Inicia sesión",
            "logo" => $logo,
            "resources" => [
                "resources/js/login.js"
            ]
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = UsersModel::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user);

            return response()->json(['authenticated' => true]);
        }

        return response()->json(['authenticated' => false, 'error' => 'No se ha encontrado ninguna cuenta con esas credenciales'])->setStatusCode(401);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $user_google = Socialite::driver('google')->user();

        session(['email' => $user_google->email, 'google_id' => $user_google->id, 'token_google' => $user_google->token]);

        return redirect('/');
    }

    public function redirectToTwitter()
    {
        return Socialite::driver('twitter')->redirect();
    }

    public function handleTwitterCallback()
    {
        $user_twitter = Socialite::driver('twitter')->user();

        session(['email' => $user_twitter->email, 'twitter_id' => $user_twitter->id, 'token_twitter' => $user_twitter->token]);

        return redirect('/');
    }

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
    {
        $user_facebook = Socialite::driver('facebook')->user();

        session(['email' => $user_facebook->email, 'facebook_id' => $user_facebook->id, 'token_facebook' => $user_facebook->token]);

        return redirect('/#');
    }

    public function redirectToLinkedin()
    {
        return Socialite::driver('linkedin-openid')->redirect();
    }

    public function handleLinkedinCallback()
    {
        $user_linkedin = Socialite::driver('linkedin-openid')->user();

        session(['email' => $user_linkedin->email, 'linkedin_id' => $user_linkedin->id, 'token_linkedin' => $user_linkedin->token]);

        return redirect('/');
    }

    public function logout()
    {
        if (Session::get('google_id')) {
            $token = Session::get('token_google');

            $client = new \GuzzleHttp\Client();

            try {
                $client->post('https://oauth2.googleapis.com/revoke', [
                    'form_params' => ['token' => $token]
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
            }
        }

        Session::flush();
        Auth::logout();

        $url_logout = "https://".env('DOMINIO_PRINCIPAL')."/login";

        return redirect($url_logout);
    }
    public function tokenLogin($token){

        $user = UsersModel::where('token_x509', $token)->first();

        if ($user){
            $url_logout = "https://".env('DOMINIO_PRINCIPAL')."/login";
            Auth::login($user);
            $this->deleteTokenLogin($user);
            return redirect("https://".env('DOMINIO_PRINCIPAL'));
        }else{
            $this->deleteTokenLogin($user);
            return redirect("https://".env('DOMINIO_PRINCIPAL')."/login?e=certificate-error");
        }
    }
    private function deleteTokenLogin($user){
        $user->token_x509 = "";
        $user->save();
    }
}
