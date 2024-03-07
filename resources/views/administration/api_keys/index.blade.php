@extends('layouts.app')

@section('content')
    <div class="poa-container">
        <h2>Claves API</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'redirection-queries-table'])

            <div>
                <button type="button" class="btn-icon" id="new-api-key-btn">
                    {{ e_heroicon('plus', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="delete-api-keys-btn">
                    {{ e_heroicon('trash', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="btn-reload-table">
                    {{ e_heroicon('arrow-path', 'outline') }}
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
