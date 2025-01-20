@extends('layouts.app')

@section('content')
    <div class="poa-container">
        <h2>Claves API</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'api-keys-table'])

            <div>
                <button type="button" class="btn-icon" id="new-api-key-btn" title="Añadir clave API">
                    {{ eHeroicon('plus', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="delete-api-keys-btn" title="Eliminar clave API">
                    {{ eHeroicon('trash', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="btn-reload-table" title="Actualizar">
                    {{ eHeroicon('arrow-path', 'outline') }}
                </button>
            </div>

        </div>

        <div class="table-container">
            <div id="api-keys-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'api-keys-table'])

        @include('administration.api_keys.api-key-modal')

        @include('partials.modal-confirmation')


    </div>
@endsection
