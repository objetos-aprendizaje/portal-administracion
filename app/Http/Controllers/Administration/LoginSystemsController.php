<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

class LoginSystemsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        return view(
            'administration.login_systems',
            [
                "page_name" => "Sistemas de inicio de sesión",
                "page_title" => "Sistemas de inicio de sesión",
                "resources" => [
                    "resources/js/administration_module/login_systems.js"
                ],
            ]
        );
    }

    public function submitGoogleForm(Request $request)
    {

        $updateData = [
            'google_login_active' => $request->input('google_login_active'),
            'google_client_id' => $request->input('google_client_id'),
            'google_client_secret' => $request->input('google_client_secret'),
        ];

        DB::transaction(function () use ($updateData, $request) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            $this->updateCache('parameters_login_systems', $request, ['google_login_active', 'google_client_id', 'google_client_secret']);

            LogsController::createLog('Actualización de inicio de sesión en Google', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });



        return response()->json(['message' => 'Login de Google guardado correctamente']);
    }

    public function submitFacebookForm(Request $request)
    {

        $updateData = [
            'facebook_login_active' => $request->input('facebook_login_active'),
            'facebook_client_id' => $request->input('facebook_client_id'),
            'facebook_client_secret' => $request->input('facebook_client_secret'),
        ];

        DB::transaction(function () use ($updateData, $request) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            $this->updateCache('parameters_login_systems', $request, ['facebook_login_active', 'facebook_client_id', 'facebook_client_secret']);

            LogsController::createLog('Actualización de inicio de sesión en Facebook', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });


        return response()->json(['message' => 'Login de Facebook guardado correctamente']);
    }

    public function submitTwitterForm(Request $request)
    {

        $updateData = [
            'twitter_login_active' => $request->input('twitter_login_active'),
            'twitter_client_id' => $request->input('twitter_client_id'),
            'twitter_client_secret' => $request->input('twitter_client_secret'),
        ];

        DB::transaction(function () use ($updateData, $request) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            $this->updateCache('parameters_login_systems', $request, ['twitter_login_active', 'twitter_client_id', 'twitter_client_secret']);

            LogsController::createLog('Actualización de inicio de sesión en Twitter', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });


        return response()->json(['message' => 'Login de Twitter guardado correctamente']);
    }

    public function submitLinkedinForm(Request $request)
    {

        $updateData = [
            'linkedin_login_active' => $request->input('linkedin_login_active'),
            'linkedin_client_id' => $request->input('linkedin_client_id'),
            'linkedin_client_secret' => $request->input('linkedin_client_secret'),
        ];

        DB::transaction(function () use ($updateData, $request) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            $this->updateCache('parameters_login_systems', $request, ['linkedin_login_active', 'linkedin_client_id', 'linkedin_client_secret']);

            LogsController::createLog('Actualización de inicio de sesión en Linkedin', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });

        return response()->json(['message' => 'Login de Linkedin guardado correctamente']);
    }

    // Actualiza la caché con los parámetros de inicio de sesión
    private function updateCache($cacheKey, $request, $fields)
    {
        // Obtén el objeto de la caché
        $parameters = Cache::get($cacheKey);

        // Actualiza las propiedades del objeto
        foreach ($fields as $field) {
            $parameters[$field] = $request->input($field);
        }

        $url = env('FRONT_URL') . '/api/update_login_system';
        $header = ['API-KEY' => env('API_KEY_FRONT')];

        // Realiza una petición POST a la URL de la api del front para que vacíe la caché de los
        // sistemas de inicio de sesión
        guzzle_call($url, null, $header, 'POST');

        // Pone el objeto actualizado de nuevo en la caché
        Cache::put($cacheKey, $parameters);
    }
}
