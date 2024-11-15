<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CertificateAccessController extends BaseController {
    use AuthorizesRequests, ValidatesRequests;

    public function index(Request $request) {

        $certificateClient = $request->header(env('FMNT_CERT_HEADER'));

        $certificateClient = "-----BEGIN CERTIFICATE-----\n"
            . wordwrap($certificateClient, 64, "\n", true)
            . "\n-----END CERTIFICATE-----";

        if (!empty($certificateClient)) {
            $validate = $this->validateClientCert($certificateClient);
        } else {
            return redirect("/login?e=certificate-error");
        }

        if ($validate) {
            $certInfo = openssl_x509_parse($certificateClient);

            $first_name = $certInfo['subject']['GN'];
            $last_name = $certInfo['subject']['SN'];
            $temp = explode("-", $certInfo['subject']['serialNumber']);
            $nif = strtoupper(trim($temp[1]));

            $data = json_encode([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'nif' => $nif,
            ]);

            $expiration = time() + 60; // 1 minute
            $hash = md5($data . $expiration . env('KEY_CHECK_CERTIFICATE_ACCESS'));
            $origin = isset($_GET['origin']) && $_GET['origin'] == 'portal_web' ? env('FRONT_URL') : env('APP_URL');

            return redirect($origin . "/login/certificate?data=" . urlencode($data) . "&expiration=" . $expiration . "&hash=" . $hash);
        } else {
            return redirect("/login?e=certificate-error");
        }
    }

    private function validateClientCert($certificateClient) {
        // Cargar el certificado de la CA
        $caCertPath = public_path('cert/AC_Raiz_FNMT-RCM_SHA256.pem');
        if (!file_exists($caCertPath)) {
            Log::error('No se encuentra el certificado de la CA en la ruta especificada');
            return false;
        }

        // Cargar el certificado como recurso X.509
        $x509 = openssl_x509_read($certificateClient);

        $certPem = '';
        openssl_x509_export($x509, $certPem);

        if (!$certPem) {
            return false;  // El certificado es inválido o no pudo ser leído
        }

        // Verificar el certificado usando OpenSSL
        $valid = openssl_x509_checkpurpose($certPem, X509_PURPOSE_SSL_CLIENT, [$caCertPath]);

        if ($valid === true) {
            return true; // El certificado es válido
        } elseif ($valid === false) {
            Log::error('Certificado no válido o no verificado');
            return false;
        } else {
            Log::error('Error al procesar la validación del certificado');
            return false;
        }
    }
}
