@extends('layouts.app')

@section('content')
    <div class="poa-container">
        <h2>Redirección de consultas por tipos de programas formativos</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'redirection-queries-table'])

            <div>
                <button type="button" class="btn-icon" id="new-redirection-query-btn">
                    {{ e_heroicon('plus', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="btn-delete-redirection-queries">
                    {{ e_heroicon('trash', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="btn-reload-table">
                    {{ e_heroicon('arrow-path', 'outline') }}
                </button>
            </div>

        </div>

        <div class="table-container">
            <div id="redirection-queries-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'redirection-queries-table'])

        @include('administration.redirection_queries_educational_program_types.redirection-query-modal')

        @include('partials.modal-confirmation')


    </div>
@endsection
