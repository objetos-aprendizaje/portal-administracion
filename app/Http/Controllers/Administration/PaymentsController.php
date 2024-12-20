<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\GeneralOptionsModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Logs\LogsController;

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
                'submenuselected' => 'administration-payments',
            ]
        );
    }


    public function savePaymentsForm(Request $request)
    {

        $errorMessages = $this->validatePaymentForm($request);

        if($errorMessages->any()) {
            return response()->json(['message' => 'Algunos campos son incorrectos', 'errors' => $errorMessages], 422);
        }

        $updateData = [
            'redsys_url' => $request->input('redsys_url'),
            'payment_gateway' => $request->input('payment_gateway'),
            'redsys_commerce_code' => $request->input('redsys_commerce_code'),
            'redsys_terminal' => $request->input('redsys_terminal'),
            'redsys_currency' => $request->input('redsys_currency'),
            'redsys_transaction_type' => $request->input('redsys_transaction_type'),
            'redsys_encryption_key' => $request->input('redsys_encryption_key'),
            'redsys_enabled' => true
        ];

        DB::transaction(function () use ($updateData) {
            foreach ($updateData as $key => $value) {
                GeneralOptionsModel::where('option_name', $key)->update(['option_value' => $value]);
            }

            LogsController::createLog('Actualización datos de redsys', 'Pagos', auth()->user()->uid);
        });

        return response()->json(['message' => 'Datos de pago guardados correctamente']);
    }

    private function validatePaymentForm($request) {
        $messages = [
            'redsys_url.required_if' => 'La URL es obligatoria',
            'redsys_commerce_code.required_if' => 'El código de comercio es obligatorio',
            'redsys_terminal.required_if' => 'El terminal es obligatorio',
            'redsys_currency.required_if' => 'La moneda es obligatoria',
            'redsys_transaction_type.required_if' => 'El tipo de transacción es obligatorio',
            'redsys_encryption_key.required_if' => 'La clave de encriptación es obligatoria',
        ];

        $rules = [
            'redsys_url' => 'required_if:payment_gateway,1,redsys',
            'redsys_commerce_code' => 'required_if:payment_gateway,1,redsys',
            'redsys_terminal' => 'required_if:payment_gateway,1,redsys',
            'redsys_currency' => 'required_if:payment_gateway,1,redsys',
            'redsys_transaction_type' => 'required_if:payment_gateway,1,redsys',
            'redsys_encryption_key' => 'required_if:payment_gateway,1,redsys',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $errorMessages = $validator->errors();

        return $errorMessages;
    }

}
