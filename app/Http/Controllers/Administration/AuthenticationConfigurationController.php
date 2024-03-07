<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;

class AuthenticationConfigurationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {

        return view(
            'administration.authentication_configuration',
            [
                "coloris" => true,
                "page_name" => "Configuración sistemas de autenticación",
                "page_title" => "Configuración sistemas de autenticación",
                "resources" => [
                ],
            ]
        );

    }

}
