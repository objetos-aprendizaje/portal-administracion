@extends('layouts.app')

@section('content')
    <div class="poa-container">
        <h2>Redirección de consultas por tipos de programas formativos</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'redirection-queries-table'])

            <div>
                <button type="button" class="btn-icon" id="new-redirection-query-btn" title="Añadir redirección de consulta">
                    {{ eHeroicon('plus', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="btn-delete-redirection-queries" title="Eliminar redirección de consulta">
                    {{ eHeroicon('trash', 'outline') }}
                </button>
                <button type="button" class="btn-icon" id="btn-reload-table" title="Actualizar">
                    {{ eHeroicon('arrow-path', 'outline') }}
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
