@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Configuración de Redsys</h2>
        <form id="payments-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="redsys_commerce_code">Código de comercio</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['redsys_commerce_code'] }}" placeholder="999008881"
                            class="poa-input" type="text" id="redsys_commerce_code" name="redsys_commerce_code" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="redsys_terminal">Terminal</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['redsys_terminal'] }}" placeholder="001"
                            type="text" id="redsys_terminal" name="redsys_terminal" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="redsys_currency">Moneda</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['redsys_currency'] }}" placeholder="978" type="text"
                            id="redsys_currency" class="poa-input" name="redsys_currency" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="redsys_transaction_type">Tipo de transacción</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['redsys_transaction_type'] }}" class="poa-input"
                            placeholder="0" type="text" id="redsys_transaction_type" name="redsys_transaction_type" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="redsys_encryption_key">Clave de encriptación</label>
                    </div>

                    <div class="content-container little">
                        <input value="{{ $general_options['redsys_encryption_key'] }}"
                            placeholder="sq7HjrUOBfKmC576ILgskD5srU870gJ7" type="text" id="redsys_encryption_key"
                            name="redsys_encryption_key" class="poa-input" />
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ e_heroicon('paper-airplane', 'outline') }}</button>

            </div>
        </form>

    </div>
@endsection
