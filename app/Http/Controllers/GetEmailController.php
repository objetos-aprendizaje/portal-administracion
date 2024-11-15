<?php

namespace App\Http\Controllers;

use App\Models\ResetPasswordTokensModel;
use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GetEmailController extends BaseController
{
    public function index(Request $request){
        return view('non_authenticated.get_email'
        )->with(
            [
                'nif' => $request->nif,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'is_new' => $request->is_new
            ]
        );
    }

}
