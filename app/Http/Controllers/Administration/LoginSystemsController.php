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
        $casActive = GeneralOptionsModel::where('option_name', 'cas_active')->where('option_value', 1)->first();
        $rediris = Saml2TenantsModel::where('key', 'rediris')->first();
        $redirisActive = GeneralOptionsModel::where('option_name', 'rediris_active')->where('option_value', 1)->first();

        $loginSaml = Saml2TenantsModel::whereIn('key', ['cas', 'rediris'])->get()->keyBy('key');

        if ($casActive) {
            $urlCasMetadata = url('saml2/' . $loginSaml['cas']->uuid . '/metadata');
        } else {
            $urlCasMetadata = false;
        }

        if ($redirisActive) {
            $urlRedirisMetadata = url('saml2/' . $loginSaml['rediris']->uuid . '/metadata');
        } else {
            $urlRedirisMetadata = false;
        }

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
                'cas_active' => $casActive,
                'rediris_active' => $redirisActive,
                'urlCasMetadata' => $urlCasMetadata,
                'urlRedirisMetadata' => $urlRedirisMetadata,
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

        $validatorErrors = $this->validateCasForm($request);

        if (!$validatorErrors->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $cas = Saml2TenantsModel::where('key', 'cas')->first();

        if (!$cas) {
            $cas = new Saml2TenantsModel();
            $cas->key = 'cas';
            $cas->uuid = generateUuid();
            $cas->name_id_format = 'persistent';
            $cas->metadata = '[]';
        }

        $cas->idp_entity_id = $request->input('cas_entity_id');
        $cas->idp_login_url = $request->input('cas_login_url');
        $cas->idp_logout_url = $request->input('cas_logout_url');
        $cas->idp_x509_cert = $request->input('cas_certificate');

        $active = intval($request->input('cas_login_active'));

        DB::transaction(function () use ($cas, $active) {
            GeneralOptionsModel::where('option_name', 'cas_active')->update(['option_value' => $active]);
            $cas->save();
            LogsController::createLog('Actualización de inicio de sesión en CAS', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });

        if ($active) {
            $urlCasMetadata = url('saml2/' . $cas->uuid . '/metadata');
        } else {
            $urlCasMetadata = false;
        }

        return response()->json(['message' => 'Login de CAS guardado correctamente', 'urlCasMetadata' => $urlCasMetadata]);
    }

    private function validateCasForm($request)
    {
        $messages = [
            'cas_entity_id.required_if' => 'El Entity ID es obligatorio',
            'cas_login_url.required_if' => 'La url de login es obligatoria',
            'cas_logout_url.required_if' => 'La url de logout es obligatoria',
            'cas_certificate.required_if' => 'El certificado es obligatorio',
        ];

        $validator = Validator::make($request->all(), [
            'cas_entity_id' => 'required_if:cas_login_active,1',
            'cas_login_url' => 'required_if:cas_login_active,1',
            'cas_logout_url' => 'required_if:cas_login_active,1',
            'cas_certificate' => 'required_if:cas_login_active,1',

        ], $messages);

        return $validator->errors();
    }

    public function submitRedirisForm(Request $request)
    {
        $validatorErrors = $this->validateRedirisForm($request);

        if (!$validatorErrors->isEmpty()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $validatorErrors], 422);
        }

        $rediris = Saml2TenantsModel::where('key', 'rediris')->first();

        if (!$rediris) {
            $rediris = new Saml2TenantsModel();
            $rediris->uuid = generateUuid();
            $rediris->key = 'rediris';
            $rediris->metadata = '[]';
            $rediris->name_id_format = 'persistent';
        }

        $rediris->idp_entity_id = $request->input('rediris_entity_id');
        $rediris->idp_login_url = $request->input('rediris_login_url');
        $rediris->idp_logout_url = $request->input('rediris_logout_url');
        $rediris->idp_x509_cert = $request->input('rediris_certificate');

        $active = intval($request->input('rediris_login_active'));

        DB::transaction(function () use ($rediris, $active) {
            GeneralOptionsModel::where('option_name', 'rediris_active')->update(['option_value' => $active]);
            $rediris->save();
            LogsController::createLog('Actualización de inicio de sesión en Rediris', 'Sistemas de inicio de sesión', auth()->user()->uid);
        });

        if ($active) {
            $urlRedirisMetadata = url('saml2/' . $rediris->uuid . '/metadata');
        } else {
            $urlRedirisMetadata = false;
        }

        return response()->json(['message' => 'Login de Rediris guardado correctamente', 'urlRedirisMetadata' => $urlRedirisMetadata]);
    }

    private function validateRedirisForm($request)
    {
        $messages = [
            'rediris_entity_id.required_if' => 'El Entity ID es obligatorio',
            'rediris_login_url.required_if' => 'La url de login es obligatoria',
            'rediris_logout_url.required_if' => 'La url de logout es obligatoria',
            'rediris_certificate.required_if' => 'El certificado es obligatorio',
        ];

        $validator = Validator::make($request->all(), [
            'rediris_entity_id' => 'required_if:rediris_login_active,1',
            'rediris_login_url' => 'required_if:rediris_login_active,1',
            'rediris_logout_url' => 'required_if:rediris_login_active,1',
            'rediris_certificate' => 'required_if:rediris_login_active,1',

        ], $messages);

        return $validator->errors();
    }
}
