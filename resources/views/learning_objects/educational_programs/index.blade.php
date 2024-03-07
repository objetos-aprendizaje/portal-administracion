@extends('layouts.app')
@section('content')
    <div class="poa-container">
        <h2>Listado de programas formativos</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'educational-resource-types-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-educational-program-btn" class="btn-icon">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-delete-educational-programs">
                        {{ e_heroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table">
                        {{ e_heroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="educational-programs-table"></div>
        </div>

        @include("partials.table-pagination", ['table' => 'educational-resource-types-table'])

    </div>

    @include('partials.modal-confirmation')
    @include('learning_objects.educational_programs.educational_program_modal')

@endsection
