@extends('layouts.app')
@section('content')
    <div class="poa-container">

        <h2>Listado de sistemas LMS</h2>

        <div class="table-control-header">
            @include('partials.table-search', ['table' => 'lms-systems-table'])

            <div class="flex gap-1">
                <div>
                    <button type="button" id="new-lms-system-btn" class="btn-icon" title="Añadir sistema LMS">
                        {{ eHeroicon('plus', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-delete-lms-system" title="Eliminar sistema LMS">
                        {{ eHeroicon('trash', 'outline') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn-icon" id="btn-reload-table" title="Actualizar">
                        {{ eHeroicon('arrow-path', 'outline') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div id="lms-systems-table"></div>
        </div>


        @include('partials.table-pagination', ['table' => 'lms-systems-table'])

    </div>

    @include('administration.lms_systems.lms_system_modal')
    @include('partials.modal-confirmation')
@endsection
