@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de Departamentos</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'departments-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-department-btn" class="btn-icon" title="Añadir departamento">
                        {{ e_heroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-delete-department" title="Eliminar departamentos">
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
            <div id="departments-table"></div>
        </div>


        @include('partials.table-pagination', ['table' => 'departments-table'])

    </div>

    @include('administration.departments.department_modal')
    @include('partials.modal-confirmation')
@endsection
