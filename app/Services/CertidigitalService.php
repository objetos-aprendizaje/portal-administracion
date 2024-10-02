<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CertidigitalService
{
    public function __construct() {}

    public function getValidToken()
    {
        $token = session('certidigital_token');
        $tokenExpires = session('certidigital_token_expires');

        if (!$token || !$tokenExpires || now()->greaterThan($tokenExpires)) {
            $token = $this->refreshToken();
        }

        return $token;
    }

    private function refreshToken()
    {
        $generalOptions = app('general_options');

        $params = [
            'grant_type' => 'password',
            'client_id' => $generalOptions['certidigital_client_id'],
            'client_secret' => $generalOptions['certidigital_client_secret'],
            'username' => $generalOptions['certidigital_username'],
            'password' => $generalOptions['certidigital_password'],
        ];

        $certidigitalUrlToken = $generalOptions['certidigital_url_token'];
        $response = Http::asForm()
            //->withoutVerifying()
            ->post($certidigitalUrlToken, $params);

        if ($response->status() != 200) {
            throw new \Exception('Error refreshing Certidigital token');
        }

        session()->put('certidigital_token', $response->json()['access_token']);
        session()->put('certidigital_token_expires', now()->addSeconds($response->json()['expires_in']));

        return $response->json()['access_token'];
    }
}
