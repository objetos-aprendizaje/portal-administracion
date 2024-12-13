<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Slides\Saml2\Events\SignedOut;
use Illuminate\Support\Facades\Auth;
use App\Models\UsersModel;
use App\Models\UserRolesModel;
use App\Models\UserRoleRelationshipsModel;
use Illuminate\Support\Facades\Redirect;
use App\Models\Saml2TenantsModel;
use App\Models\UsersAccessesModel;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
            $_SERVER['REQUEST_SCHEME'] = 'https';
            $_SERVER['SERVER_PORT'] = '443';
        } else {
            URL::forceScheme('http');
        }

        \Carbon\Carbon::setLocale(config('app.locale'));

        Event::listen(\Slides\Saml2\Events\SignedIn::class, function (\Slides\Saml2\Events\SignedIn $event) {

            // your own code preventing reuse of a $messageId to stop replay attacks
            $samlUser = $event->getSaml2User();

            $userDataSaml = [
                'id' => $samlUser->getUserId(),
                'attributes' => $samlUser->getAttributes(),
                'assertion' => $samlUser->getRawSamlAssertion()
            ];

            $dataLogin = $this->getDataLogin($userDataSaml, $event);

            if (!$dataLogin["email"]) Redirect::to('/')->send();

            $user = UsersModel::with('roles')
                ->whereHas('roles', function ($query) {
                    $query->whereIn('code', ['ADMINISTRATOR', 'MANAGEMENT', 'TEACHER']);
                })
                ->where('email', strtolower($dataLogin["email"]))
                ->first();

            DB::transaction(function () use ($dataLogin, &$user) {
                if (!$user) $user = $this->registerCasRediris($dataLogin);
                if (!$user->hasAnyRole(['TEACHER'])) $this->addRoleTeacher($user);

                // Registro de la sesión
                UsersAccessesModel::insert([
                    'uid' => generate_uuid(),
                    'user_uid' => $user->uid,
                    'date' => date('Y-m-d H:i:s')
                ]);
            });

            Auth::login($user);
        });

        Event::listen('Slides\Saml2\Events\SignedOut', function (SignedOut $event) {
            Auth::logout();
        });
    }

    private function registerCasRediris($dataLogin)
    {
        $newUser = new UsersModel();
        $newUser->uid = generate_uuid();
        $newUser->first_name = $dataLogin["email"];
        $newUser->email = $dataLogin["email"];
        $newUser->identity_verified = true;

        if (isset($dataLogin["nif"])) $newUser->nif = $dataLogin["nif"];

        $newUser->save();
        return $newUser;
    }

    private function addRoleTeacher($user)
    {
        $rol = UserRolesModel::where("code", "TEACHER")->first();
        $rol_relation = new UserRoleRelationshipsModel();
        $rol_relation->uid = generate_uuid();
        $rol_relation->user_uid = $user->uid;
        $rol_relation->user_role_uid = $rol->uid;
        $rol_relation->save();
    }

    private function getDataLogin($userDataSaml, $event)
    {
        $loginSystem = $this->detectLoginSystem();
        $dataLogin = [];

        if ($loginSystem == "cas") {
            $dataLogin = [
                "email" => $userDataSaml['id'],
                "nif" => $userDataSaml['attributes']['sPUID'][0],
            ];
        } else if ($loginSystem == "rediris") {
            $xml = $event->getAuth()->getBase();
            $xml = $xml->getLastResponseXML();
            $xmlObj = simplexml_load_string($xml, null, null, "urn:oasis:names:tc:SAML:2.0:assertion", true);
            // Busca todos los elementos de AuthenticatingAuthority
            $namespaces = $xmlObj->getNamespaces(true);
            $authAuthorities = $xmlObj->xpath('//saml:AuthenticatingAuthority');
            $results = [];
            $access = false;
            if ($authAuthorities) {
                foreach ($authAuthorities as $authority) {
                    $results[] = $authority;
                    $temp = explode("/", $authority);
                    if ($temp[count($temp) - 1] == "UMCAS") {
                        $access = true;
                    }
                }
            }
            if ($access == false) Redirect::to('/')->send();
            $dataLogin = [
                "email" => $userDataSaml['attributes']['urn:oid:0.9.2342.19200300.100.1.3'][0],
            ];
        }

        return $dataLogin;
    }


    private function detectLoginSystem()
    {
        $path = request()->path();
        $uuid = explode("/", $path);
        $tenantKey = Saml2TenantsModel::where('uuid', $uuid[1])->first();
        return $tenantKey->key;
    }
}
