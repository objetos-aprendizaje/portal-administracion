@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Configuración de Certidigital</h2>
        <form id="certidigital-form">
            @csrf

            <div class="poa-form">

                <div class="field">
                    <div class="label-container label-center">
                        <label for="certidigital_url">URL</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['certidigital_url'] }}" placeholder="https://certidigital-k8s.atica.um.es/realms/certidigi/protocol/openid-connect/token"
                            class="poa-input" type="text" id="certidigital_url" name="certidigital_url" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="certidigital_client_id">ID Cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['certidigital_client_id'] }}" placeholder=""
                            type="text" id="certidigital_client_id" name="certidigital_client_id" class="poa-input" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="certidigital_client_secret">Clave secreta de cliente</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['certidigital_client_secret'] }}" placeholder="" type="text"
                            id="certidigital_client_secret" class="poa-input" name="certidigital_client_secret" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="certidigital_username">Usuario</label>
                    </div>
                    <div class="content-container little">
                        <input value="{{ $general_options['certidigital_username'] }}" class="poa-input"
                            placeholder="" type="text" id="certidigital_username" name="certidigital_username" />
                    </div>
                </div>

                <div class="field">
                    <div class="label-container label-center">
                        <label for="certidigital_password">Clave</label>
                    </div>

                    <div class="content-container little">
                        <input value="{{ $general_options['certidigital_password'] }}"
                            placeholder="" type="text" id="certidigital_password"
                            name="certidigital_password" class="poa-input" />
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">Guardar
                    {{ e_heroicon('paper-airplane', 'outline') }}</button>

            </div>
        </form>

    </div>
@endsection
