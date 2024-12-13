@extends('layouts.app')

@section('content')
    <div class="poa-container">

        <h2>Convocatorias creadas</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'calls-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-call-btn" class="btn-icon" id="btn-edit-call" title="Crear convocatoria">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-edit-call" title="Eliminar convocatorias">
                        {{ e_heroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table" title="Actualizar">
                        {{ e_heroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="calls-table"></div>
        </div>

        @include('partials.table-pagination', ['table' => 'calls-table'])

    </div>

    @include('management.calls.call_modal')
    @include('partials.modal-confirmation')
@endsection
