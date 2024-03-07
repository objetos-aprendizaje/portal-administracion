<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;

class PaymentsController extends BaseController {
    use AuthorizesRequests, ValidatesRequests;


    public function index()
    {

        return view(
            'administration.payments',
            [
                "page_name" => "Pagos",
                "page_title" => "Pagos",
                "resources" => [
                    "resources/js/administration_module/payments.js"
                ],
            ]
        );
    }


    public function savePaymentsForm(Request $request)
    {
        $updateData = [
            'redsys_commerce_code' => $request->input('redsys_commerce_code'),
            'redsys_terminal' => $request->input('redsys_terminal'),
            'redsys_currency' => $request->input('redsys_currency'),
            'redsys_transaction_type' => $request->input('redsys_transaction_type'),
            'redsys_encryption_key' => $request->input('redsys_encryption_key')
        ];

        foreach ($updateData as $key => $value) {
            GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
        }

        return response()->json(['message' => 'Datos de pago guardados correctamente']);
    }

}
