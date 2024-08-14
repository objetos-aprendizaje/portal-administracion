<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;
use App\Models\Saml2TenantsModel;
use Illuminate\Support\Facades\Validator;

class LoginSystemsController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        $cas = Saml2TenantsModel::where('key', 'cas')->first();
        $cas_active = GeneralOptionsModel::where('option_name', 'cas_active')->where('option_value', 1)->first();
        $rediris = Saml2TenantsModel::where('key', 'rediris')->first();
        $rediris_active = GeneralOptionsModel::where('option_name', 'rediris_active')->where('option_value', 1)->first();

        return view(
            'administration.login_systems',
            [
                "page_name" => "Sistemas de inicio de sesión",
                "page_title" => "Sistemas de inicio de sesión",
                "resources" => [
                    "resources/js/administration_module/login_systems.js"
                ],
                'cas' => $cas,
                'rediris' => $rediris,
                'cas_active' => $cas_active,
                'rediris_active' => $rediris_active,
                "submenuselected" => "login-systems",
            ]
        );
    }

    public function submitGoogleForm(Request $request)
    {

        $messages = [
            'google_client_id.required_if' => 'El ID de cliente es obligatorio',
            'google_client_secret.required_if' => 'La clave secreta es obligatoria',
        ];

        $validator = Validator::make($request->all(), [
            'google_login_active' => 'required|boolean',
            'google_client_id' => 'required_if:google_login_active,1',
            'google_client_secret' => 'required_if:google_login_active,1',
        ], $messages);

        $validatorErrors = $validator->errors();

        if (!$validator->errors()->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $updateData = [
            'google_login_active' => $request->input('google_login_active'),
            'google_client_id' => $request->input('google_client_id'),
            'google_client_secret' => $request->input('google_client_secret'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de inicio de sesión en Google', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });



        return response()->json(['message' => 'Login de Google guardado correctamente']);
    }

    public function submitFacebookForm(Request $request)
    {

        $messages = [
            'facebook_client_id.required_if' => 'El ID de cliente es obligatorio',
            'facebook_client_secret.required_if' => 'La clave secreta es obligatoria',
        ];

        $validator = Validator::make($request->all(), [
            'facebook_client_id' => 'required_if:facebook_login_active,1',
            'facebook_client_secret' => 'required_if:facebook_login_active,1',
        ], $messages);

        $validatorErrors = $validator->errors();

        if (!$validator->errors()->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $updateData = [
            'facebook_login_active' => $request->input('facebook_login_active'),
            'facebook_client_id' => $request->input('facebook_client_id'),
            'facebook_client_secret' => $request->input('facebook_client_secret'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de inicio de sesión en Facebook', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });


        return response()->json(['message' => 'Login de Facebook guardado correctamente']);
    }

    public function submitTwitterForm(Request $request)
    {
        $messages = [
            'twitter_client_id.required_if' => 'El ID de cliente es obligatorio',
            'twitter_client_secret.required_if' => 'La clave secreta es obligatoria',
        ];

        $validator = Validator::make($request->all(), [
            'twitter_client_id' => 'required_if:twitter_login_active,1',
            'twitter_client_secret' => 'required_if:twitter_login_active,1',
        ], $messages);

        $validatorErrors = $validator->errors();

        if (!$validator->errors()->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $updateData = [
            'twitter_login_active' => $request->input('twitter_login_active'),
            'twitter_client_id' => $request->input('twitter_client_id'),
            'twitter_client_secret' => $request->input('twitter_client_secret'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de inicio de sesión en Twitter', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });


        return response()->json(['message' => 'Login de Twitter guardado correctamente']);
    }

    public function submitLinkedinForm(Request $request)
    {
        $messages = [
            'linkedin_client_id.required_if' => 'El ID de cliente es obligatorio',
            'linkedin_client_secret.required_if' => 'La clave secreta es obligatoria',
        ];

        $validator = Validator::make($request->all(), [
            'linkedin_client_id' => 'required_if:linkedin_login_active,1',
            'linkedin_client_secret' => 'required_if:linkedin_login_active,1',
        ], $messages);

        $validatorErrors = $validator->errors();

        if (!$validator->errors()->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $updateData = [
            'linkedin_login_active' => $request->input('linkedin_login_active'),
            'linkedin_client_id' => $request->input('linkedin_client_id'),
            'linkedin_client_secret' => $request->input('linkedin_client_secret'),
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización de inicio de sesión en Linkedin', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });

        return response()->json(['message' => 'Login de Linkedin guardado correctamente']);
    }

    public function submitCasForm(Request $request)
    {
        $messages = [
            'cas_entity_id.required' => 'El Entity ID es obligatorio',
            'cas_login_url.required' => 'La url de login es obligatoria',
            'cas_logout_url.required' => 'La url de logout es obligatoria',
            'cas_certificate.required' => 'El certificado es obligatorio',
        ];

        $validator = Validator::make($request->all(), [
            'cas_entity_id' => 'required|string',
            'cas_login_url' => 'required|string',
            'cas_logout_url' => 'required|string',
            'cas_certificate' => 'required|string',

        ], $messages);

        $validatorErrors = $validator->errors();

        if (!$validator->errors()->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $active = intval($request->input('cas_login_active'));

        if ($active) {
            GeneralOptionsModel::where('option_name', 'cas_active')->update(['option_value' => $active]);
        } else {
            GeneralOptionsModel::where('option_name', 'cas_active')->update(['option_value' => $active]);
        }

        $cas = Saml2TenantsModel::where('key', 'cas')->first();

        if ($cas) {

            DB::transaction(function () use ($cas, $request) {

                $cas->idp_entity_id = $request->input('cas_entity_id');
                $cas->idp_login_url = $request->input('cas_login_url');
                $cas->idp_logout_url = $request->input('cas_logout_url');
                $cas->idp_x509_cert = $request->input('cas_certificate');
                $cas->metadata = '[]';
                $cas->name_id_format = 'persistent';

                $cas->save();

                LogsController::createLog('Actualización de inicio de sesión en CAS', 'Sistemas de inicio de sesión', auth()->user()->uid);
            });
            return response()->json(['message' => 'Login de CAS guardado correctamente']);
        } else {

            DB::transaction(function () use ($request) {
                $uid = generate_uuid();

                $new_data = new Saml2TenantsModel();
                $new_data->uuid = $uid;
                $new_data->key = 'cas';
                $new_data->idp_entity_id = $request->input('cas_entity_id');
                $new_data->idp_login_url = $request->input('cas_login_url');
                $new_data->idp_logout_url = $request->input('cas_logout_url');
                $new_data->idp_x509_cert = $request->input('cas_certificate');
                $new_data->metadata = '[]';
                $new_data->name_id_format = 'persistent';

                $new_data->save();

                LogsController::createLog('Actualización de inicio de sesión en CAS', 'Sistemas de inicio de sesión', auth()->user()->uid);
            });

            return response()->json(['message' => 'Login de CAS guardado correctamente']);
        }
    }

    public function submitRedirisForm(Request $request)
    {

        $messages = [
            'rediris_entity_id.required' => 'El Entity ID es obligatorio',
            'rediris_login_url.required' => 'La url de login es obligatoria',
            'rediris_logout_url.required' => 'La url de logout es obligatoria',
            'rediris_certificate.required' => 'El certificado es obligatorio',
        ];

        $validator = Validator::make($request->all(), [
            'rediris_entity_id' => 'required|string',
            'rediris_login_url' => 'required|string',
            'rediris_logout_url' => 'required|string',
            'rediris_certificate' => 'required|string',

        ], $messages);

        $validatorErrors = $validator->errors();

        if (!$validator->errors()->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $active = intval($request->input('rediris_login_active'));

        if ($active) {
            GeneralOptionsModel::where('option_name', 'rediris_active')->update(['option_value' => $active]);
        } else {
            GeneralOptionsModel::where('option_name', 'rediris_active')->update(['option_value' => $active]);
        }

        $rediris = Saml2TenantsModel::where('key', 'rediris')->first();

        if ($rediris) {

            DB::transaction(function () use ($rediris, $request) {

                $rediris->idp_entity_id = $request->input('rediris_entity_id');
                $rediris->idp_login_url = $request->input('rediris_login_url');
                $rediris->idp_logout_url = $request->input('rediris_logout_url');
                $rediris->idp_x509_cert = $request->input('rediris_certificate');
                $rediris->metadata = '[]';
                $rediris->name_id_format = 'persistent';

                $rediris->save();

                LogsController::createLog('Actualización de inicio de sesión en REDIRIS', 'Sistemas de inicio de sesión', auth()->user()->uid);
            });
            return response()->json(['message' => 'Login de REDIRIS guardado correctamente']);
        } else {

            DB::transaction(function () use ($request) {
                $uid = generate_uuid();

                $new_data = new Saml2TenantsModel();
                $new_data->uuid = $uid;
                $new_data->key = 'rediris';
                $new_data->idp_entity_id = $request->input('rediris_entity_id');
                $new_data->idp_login_url = $request->input('rediris_login_url');
                $new_data->idp_logout_url = $request->input('rediris_logout_url');
                $new_data->idp_x509_cert = $request->input('rediris_certificate');
                $new_data->metadata = '[]';
                $new_data->name_id_format = 'persistent';

                $new_data->save();

                LogsController::createLog('Actualización de inicio de sesión en REDIRIS', 'Sistemas de inicio de sesión', auth()->user()->uid);
            });

            return response()->json(['message' => 'Login de REDIRIS guardado correctamente']);
        }
    }
}
